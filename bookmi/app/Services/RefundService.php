<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Enums\BookingStatus;
use App\Enums\EscrowStatus;
use App\Enums\TransactionStatus;
use App\Exceptions\RefundException;
use App\Models\BookingRequest;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class RefundService
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
    ) {}

    /**
     * Process a refund for a disputed booking.
     *
     * Admin-only action. Pattern (same as PaymentService):
     * 1. Short DB transaction: lock + validate → mark as Refunded (optimistic lock).
     * 2. HTTP refund call OUTSIDE DB transaction (no connection held during 15s call).
     * 3. On success: update with refund reference.
     * 4. On failure: reset status to Succeeded + re-throw.
     *
     * @throws RefundException if validation fails
     * @throws \App\Exceptions\PaymentException if gateway errors
     */
    public function processRefund(BookingRequest $booking, int $refundAmount, string $reason): void
    {
        // ── Step 1: Short DB transaction — lock + validate + optimistic mark ──
        $transaction = DB::transaction(function () use ($booking, $refundAmount, $reason) {
            $fresh = Transaction::where('booking_request_id', $booking->id)
                ->where('status', TransactionStatus::Succeeded->value)
                ->lockForUpdate()
                ->first();

            if (! $fresh) {
                throw RefundException::noSucceededTransaction();
            }

            if ($refundAmount > $fresh->amount) {
                throw RefundException::amountExceedsTransaction($refundAmount, $fresh->amount);
            }

            // Optimistic mark: set Refunded immediately to prevent concurrent refunds.
            // If the HTTP call fails, we reset this back to Succeeded.
            $fresh->update([
                'status'        => TransactionStatus::Refunded->value,
                'refund_amount' => $refundAmount,
                'refund_reason' => $reason,
                'refunded_at'   => now(),
            ]);

            return $fresh;
        });

        // ── Step 2: HTTP call OUTSIDE DB transaction ──
        try {
            $refundData = $this->gateway->refundTransaction(
                $transaction->gateway_reference ?? $transaction->idempotency_key,
                $refundAmount,
                $reason,
            );
        } catch (\Throwable $e) {
            // Reset to Succeeded so a retry is possible
            $transaction->update(['status' => TransactionStatus::Succeeded->value, 'refund_amount' => null, 'refund_reason' => null, 'refunded_at' => null]);
            throw $e;
        }

        // ── Step 3: Persist gateway reference + cascade updates ──
        DB::transaction(function () use ($transaction, $refundData, $booking) {
            $transaction->update([
                'refund_reference' => $refundData['id'] ?? $refundData['reference'] ?? null,
            ]);

            // Reload relation to avoid caching stale state (e.g. concurrent escrow release)
            if ($escrow = $transaction->fresh()->escrowHold) {
                $escrow->update(['status' => EscrowStatus::Refunded->value]);
            }

            // Transition booking to Cancelled
            $booking->update(['status' => BookingStatus::Cancelled->value]);
        });
    }
}
