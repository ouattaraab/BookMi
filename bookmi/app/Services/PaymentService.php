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
    ) {}

    /**
     * Initiate a Mobile Money payment via Paystack.
     *
     * Pattern: short DB tx (lock + create) → HTTP call outside tx → update record.
     * This prevents holding a DB connection during the HTTP call (up to 15s per NFR4).
     *
     * 1. Validate booking is accepted
     * 2. Check method is mobile money
     * 3. Eager-load client relation
     * 4. Short DB transaction: lock-check duplicate + create record (initiated)
     * 5. Call gateway OUTSIDE DB transaction
     * 6. On failure: mark transaction failed, re-throw
     * 7. On success: update to processing with gateway reference
     */
    public function initiatePayment(BookingRequest $booking, array $data): Transaction
    {
        if ($booking->status !== BookingStatus::Accepted) {
            throw PaymentException::bookingNotPayable($booking->status->value);
        }

        $paymentMethod = PaymentMethod::from($data['payment_method']);

        // Guard: this endpoint is mobile-money only
        if (! $paymentMethod->isMobileMoney()) {
            throw PaymentException::unsupportedMethod($paymentMethod->value);
        }

        // Eager-load client to avoid N+1 inside the gateway call
        $booking->loadMissing('client');

        $idempotencyKey = Str::uuid()->toString();

        // ── Short DB transaction: duplicate check (with lock) + record creation ──
        $transaction = DB::transaction(function () use ($booking, $paymentMethod, $idempotencyKey) {
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
                'amount'             => $booking->total_amount,
                'currency'           => 'XOF',
                'gateway'            => $this->gateway->name(),
                'status'             => TransactionStatus::Initiated->value,
                'idempotency_key'    => $idempotencyKey,
                'initiated_at'       => now(),
            ]);
        });

        // ── HTTP call OUTSIDE DB transaction — does not hold DB connection ──
        $payload = [
            'email'        => $booking->client->email,
            'amount'       => $booking->total_amount,
            'currency'     => 'XOF',
            'reference'    => $idempotencyKey,
            'mobile_money' => [
                'phone'    => $data['phone_number'],
                'provider' => $paymentMethod->paystackProvider(),
            ],
        ];

        try {
            $result = $this->gateway->initiateCharge($payload);
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
}
