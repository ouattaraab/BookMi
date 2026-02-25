<?php

namespace App\Jobs;

use App\Enums\BookingStatus;
use App\Enums\EscrowStatus;
use App\Enums\PayoutStatus;
use App\Enums\TransactionStatus;
use App\Events\PaymentReceived;
use App\Models\BookingRequest;
use App\Models\EscrowHold;
use App\Models\Payout;
use App\Models\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class HandlePaymentWebhook implements ShouldQueue
{
    use Queueable;

    /** Maximum number of attempts (NFR35 — 5 retries). */
    public int $tries = 5;

    /**
     * Backoff in seconds between retries: 10s, 30s, 90s, 270s, 810s.
     * Exponential backoff × 3 each step.
     *
     * @var array<int>
     */
    public array $backoff = [10, 30, 90, 270, 810];

    public function __construct(
        public readonly string $event,
        public readonly array $data,
    ) {
        $this->onQueue('payments');
    }

    public function handle(): void
    {
        match ($this->event) {
            'charge.success'   => $this->handleChargeSuccess(),
            'charge.failed'    => $this->handleChargeFailure(),
            'transfer.success' => $this->handleTransferSuccess(),
            'transfer.failed'  => $this->handleTransferFailure(),
            default            => null, // unknown events are silently ignored
        };
    }

    // ── charge.success ────────────────────────────────────────────────────

    private function handleChargeSuccess(): void
    {
        $reference = $this->data['reference'] ?? null;

        if (! $reference) {
            return;
        }

        $transaction = Transaction::where('idempotency_key', $reference)
            ->orWhere('gateway_reference', $reference)
            ->first();

        if (! $transaction) {
            return;
        }

        $escrowHold = null;

        DB::transaction(function () use ($transaction, &$escrowHold) {
            // H1 fix: idempotency guard WITH lock — prevents duplicate EscrowHold
            // under concurrent webhook retries (TOCTOU race condition).
            $fresh = Transaction::where('id', $transaction->id)
                ->lockForUpdate()
                ->first();

            if (! $fresh || $fresh->status === TransactionStatus::Succeeded) {
                return;
            }

            $fresh->update([
                'status'       => TransactionStatus::Succeeded->value,
                'completed_at' => now(),
            ]);

            // H2 fix: throw RuntimeException so DB::transaction rolls back
            // if the booking is missing (data integrity guard).
            $booking = BookingRequest::find($fresh->booking_request_id);

            if (! $booking) {
                throw new \RuntimeException(
                    "handleChargeSuccess: booking #{$fresh->booking_request_id} not found for transaction #{$fresh->id}"
                );
            }

            $previousStatus = $booking->status;
            $booking->update(['status' => BookingStatus::Paid->value]);

            \App\Models\BookingStatusLog::create([
                'booking_request_id' => $booking->id,
                'from_status'        => $previousStatus instanceof BookingStatus ? $previousStatus->value : $previousStatus,
                'to_status'          => BookingStatus::Paid->value,
                'performed_by_id'    => $booking->client_id,
            ]);

            $escrowHold = EscrowHold::create([
                'transaction_id'       => $fresh->id,
                'booking_request_id'   => $booking->id,
                'cachet_amount'        => $booking->cachet_amount,
                'commission_amount'    => $booking->commission_amount,
                'total_amount'         => $booking->total_amount,
                'status'               => EscrowStatus::Held->value,
                'held_at'              => now(),
                'release_scheduled_at' => now()->addHours(
                    config('bookmi.escrow.auto_confirm_hours', 48)
                ),
            ]);

        });

        // Emit event AFTER transaction commits (so listeners see committed data)
        if ($escrowHold) {
            PaymentReceived::dispatch($transaction->fresh(), $escrowHold);
        }
    }

    // ── transfer.success ──────────────────────────────────────────────────

    private function handleTransferSuccess(): void
    {
        $transferCode = $this->data['transfer_code'] ?? null;

        if (! $transferCode) {
            return;
        }

        $payout = Payout::where('gateway_reference', $transferCode)->first();

        if (! $payout) {
            return;
        }

        // Idempotency: already in terminal state
        if ($payout->status === PayoutStatus::Succeeded) {
            return;
        }

        $payout->update(['status' => PayoutStatus::Succeeded->value]);
    }

    // ── transfer.failed ───────────────────────────────────────────────────

    private function handleTransferFailure(): void
    {
        $transferCode = $this->data['transfer_code'] ?? null;

        if (! $transferCode) {
            return;
        }

        $payout = Payout::where('gateway_reference', $transferCode)->first();

        if (! $payout) {
            return;
        }

        if (in_array($payout->status, [PayoutStatus::Succeeded, PayoutStatus::Failed], strict: true)) {
            return;
        }

        $payout->update(['status' => PayoutStatus::Failed->value]);
    }

    // ── charge.failed ─────────────────────────────────────────────────────

    private function handleChargeFailure(): void
    {
        $reference = $this->data['reference'] ?? null;

        if (! $reference) {
            return;
        }

        $transaction = Transaction::where('idempotency_key', $reference)
            ->orWhere('gateway_reference', $reference)
            ->first();

        if (! $transaction) {
            return;
        }

        // Idempotency: already in a terminal state
        if (in_array($transaction->status, [TransactionStatus::Failed, TransactionStatus::Succeeded], strict: true)) {
            return;
        }

        $transaction->update(['status' => TransactionStatus::Failed->value]);
    }
}
