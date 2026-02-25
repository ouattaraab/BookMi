<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use App\Exceptions\PaymentException;
use App\Models\BookingRequest;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
    ) {
    }

    /**
     * Initiate a Paystack payment for a booking.
     *
     * Pattern: short DB tx (lock + create) → HTTP call outside tx → update record.
     * This prevents holding a DB connection during the HTTP call (up to 15s per NFR4).
     *
     * 1. Validate booking is payable (pending = instant-book auto-accept, or accepted)
     * 2. Eager-load client relation
     * 3. Short DB transaction: lock-check duplicate + auto-accept if pending + create record
     * 4. Call gateway OUTSIDE DB transaction
     * 5. On failure: mark transaction failed, re-throw
     * 6. On success: update to processing with gateway reference
     */
    public function initiatePayment(BookingRequest $booking, array $data): Transaction
    {
        // Accept both pending (instant-book flow) and already-accepted bookings.
        // Cancelled, completed, or other statuses are not payable.
        $payableStatuses = [BookingStatus::Pending, BookingStatus::Accepted];
        if (! in_array($booking->status, $payableStatuses, true)) {
            throw PaymentException::bookingNotPayable($booking->status->value);
        }

        $paymentMethod = PaymentMethod::from($data['payment_method']);

        // Eager-load client to avoid N+1 inside the gateway call
        $booking->loadMissing('client');

        $idempotencyKey = Str::uuid()->toString();

        // ── Short DB transaction: duplicate check (with lock) + record creation ──
        $transaction = DB::transaction(function () use ($booking, $paymentMethod, $idempotencyKey) {
            // Re-check booking status WITH lock to prevent race conditions.
            $lockedBooking = BookingRequest::where('id', $booking->id)
                ->lockForUpdate()
                ->first();

            $payableStatuses = [BookingStatus::Pending, BookingStatus::Accepted];
            if (! $lockedBooking || ! in_array($lockedBooking->status, $payableStatuses, true)) {
                throw PaymentException::bookingNotPayable($lockedBooking?->status->value ?? 'unknown');
            }

            // Auto-accept pending bookings (instant-book flow: client pays immediately).
            if ($lockedBooking->status === BookingStatus::Pending) {
                $lockedBooking->update(['status' => BookingStatus::Accepted]);
            }

            $hasPending = Transaction::where('booking_request_id', $booking->id)
                ->whereIn('status', [TransactionStatus::Initiated->value, TransactionStatus::Processing->value])
                ->lockForUpdate()
                ->exists();

            if ($hasPending) {
                throw PaymentException::duplicateTransaction();
            }

            return Transaction::create([
                'booking_request_id' => $booking->id,
                'payment_method'     => $paymentMethod->value,
                'amount'             => $lockedBooking->total_amount,
                'currency'           => 'XOF',
                'gateway'            => $this->gateway->name(),
                'status'             => TransactionStatus::Initiated->value,
                'idempotency_key'    => $idempotencyKey,
                'initiated_at'       => now(),
            ]);
        });

        // ── HTTP call OUTSIDE DB transaction — does not hold DB connection ──
        try {
            if ($paymentMethod->isMobileMoney()) {
                $result = $this->initiateChargeForMobileMoney(
                    $booking,
                    $paymentMethod,
                    $idempotencyKey,
                    $data['phone_number'] ?? '',
                );
            } else {
                $result = $this->initializeTransactionForCard(
                    $booking,
                    $paymentMethod,
                    $idempotencyKey,
                );
            }
        } catch (PaymentException $e) {
            // Mark transaction as failed for observability before re-throwing
            $transaction->update(['status' => TransactionStatus::Failed->value]);
            throw $e;
        }

        $transaction->update([
            'status'            => TransactionStatus::Processing->value,
            'gateway_reference' => $result['reference'] ?? $idempotencyKey,
            'gateway_response'  => $result,
        ]);

        return $transaction->fresh();
    }

    /** @return array<string, mixed> */
    private function initiateChargeForMobileMoney(
        BookingRequest $booking,
        PaymentMethod $paymentMethod,
        string $idempotencyKey,
        string $phoneNumber,
    ): array {
        return $this->gateway->initiateCharge([
            'email'        => $booking->client->email,
            'amount'       => $booking->total_amount * 100,
            'currency'     => 'XOF',
            'reference'    => $idempotencyKey,
            'mobile_money' => [
                'phone'    => $phoneNumber,
                'provider' => $paymentMethod->paystackProvider(),
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function initializeTransactionForCard(
        BookingRequest $booking,
        PaymentMethod $paymentMethod,
        string $idempotencyKey,
    ): array {
        $channels = match ($paymentMethod) {
            PaymentMethod::Card         => ['card'],
            PaymentMethod::BankTransfer => ['bank_transfer'],
            default                     => ['card'],
        };

        return $this->gateway->initializeTransaction([
            'email'        => $booking->client->email,
            'amount'       => $booking->total_amount * 100,
            'currency'     => 'XOF',
            'reference'    => $idempotencyKey,
            'callback_url' => config('bookmi.payment.callback_url'),
            'channels'     => $channels,
        ]);
    }

    /**
     * Submit OTP for a pending mobile money charge.
     *
     * Finds the transaction by idempotency_key (= the reference sent to Paystack),
     * validates it is still in processing state, then forwards the OTP to Paystack.
     *
     * @return array<string, mixed> Gateway response (status, display_text, etc.)
     */
    public function submitOtp(string $reference, string $otp): array
    {
        $transaction = Transaction::where('idempotency_key', $reference)->firstOrFail();

        if ($transaction->status !== TransactionStatus::Processing) {
            throw PaymentException::transactionNotProcessing($transaction->status->value);
        }

        return $this->gateway->submitOtp($reference, $otp);
    }
}
