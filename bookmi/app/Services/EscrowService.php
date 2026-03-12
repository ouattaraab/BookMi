<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\EscrowStatus;
use App\Enums\TransactionStatus;
use App\Events\EscrowReleased;
use App\Exceptions\EscrowException;
use App\Models\BookingRequest;
use App\Models\EscrowHold;
use App\Models\TalentProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EscrowService
{
    /**
     * Release an escrow hold and transition booking to Confirmed.
     *
     * Can be called manually (client confirm_delivery) or automatically (ReleaseExpiredEscrows).
     * Pattern: short DB transaction (lock + update) → event AFTER commit.
     * lockForUpdate() prevents duplicate releases under concurrent calls (TOCTOU guard).
     */
    public function releaseEscrow(EscrowHold $hold, string $releasedByType = 'system', ?int $releasedById = null): void
    {
        $wasReleased = false;

        DB::transaction(function () use ($hold, $releasedByType, $releasedById, &$wasReleased) {
            $fresh = EscrowHold::where('id', $hold->id)->lockForUpdate()->first();

            if (! $fresh || $fresh->status !== EscrowStatus::Held) {
                // Idempotency: already released or in another terminal state
                return;
            }

            $fresh->update([
                'status'            => EscrowStatus::Released->value,
                'released_at'       => now(),
                'released_by_type'  => $releasedByType,
                'released_by'       => $releasedById,
            ]);

            // Transition booking to Confirmed if still in Paid status.
            // lockForUpdate prevents a concurrent cancellation from being overwritten.
            $booking = BookingRequest::where('id', $fresh->booking_request_id)
                ->lockForUpdate()
                ->first();

            if ($booking && $booking->status === BookingStatus::Paid) {
                $booking->update(['status' => BookingStatus::Confirmed->value]);
            }

            $wasReleased = true;
        });

        // Dispatch event AFTER the transaction commits (listeners see committed data)
        if ($wasReleased) {
            EscrowReleased::dispatch($hold->fresh());
        }
    }

    /**
     * Client confirms delivery — triggers manual escrow release.
     *
     * Validates:
     * - $client is the booking owner
     * - booking is in Paid status
     * - a held escrow exists for this booking
     */
    public function confirmDelivery(BookingRequest $booking, User $client): void
    {
        if ($booking->client_id !== $client->id) {
            throw EscrowException::forbidden();
        }

        if ($booking->status !== BookingStatus::Paid) {
            throw EscrowException::bookingNotConfirmable($booking->status->value);
        }

        // [C4] Guard: require a succeeded transaction before releasing funds.
        $hasSucceededTransaction = $booking->transactions()
            ->where('status', TransactionStatus::Succeeded->value)
            ->exists();

        if (! $hasSucceededTransaction) {
            throw EscrowException::escrowNotHeld('no_succeeded_transaction');
        }

        $hold = EscrowHold::where('booking_request_id', $booking->id)
            ->where('status', EscrowStatus::Held->value)
            ->first();

        if (! $hold) {
            throw EscrowException::escrowNotHeld('not_found');
        }

        $this->releaseEscrow($hold, 'client', $client->id);
    }

    /**
     * Talent confirms delivery as fallback — only allowed 24 h after event_date
     * when the client has not yet confirmed.
     *
     * Validates:
     * - $talent owns the TalentProfile linked to this booking
     * - booking is in Paid status
     * - now() >= event_date + 24 h
     * - a held escrow exists for this booking
     */
    public function talentConfirmDelivery(BookingRequest $booking, User $talent): void
    {
        $isTalent = TalentProfile::where('id', $booking->talent_profile_id)
            ->where('user_id', $talent->id)
            ->exists();

        if (! $isTalent) {
            throw EscrowException::forbidden();
        }

        if ($booking->status !== BookingStatus::Paid) {
            $currentStatus = $booking->status instanceof BookingStatus ? $booking->status->value : '';
            throw EscrowException::bookingNotConfirmable($currentStatus);
        }

        if (now()->lt(\Carbon\Carbon::parse($booking->event_date)->addDay())) {
            throw EscrowException::tooEarlyForTalentConfirm();
        }

        $hold = EscrowHold::where('booking_request_id', $booking->id)
            ->where('status', EscrowStatus::Held->value)
            ->first();

        if (! $hold) {
            throw EscrowException::escrowNotHeld('not_found');
        }

        $this->releaseEscrow($hold, 'talent', $talent->id);
    }
}
