<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\EscrowStatus;
use App\Events\EscrowReleased;
use App\Exceptions\EscrowException;
use App\Models\BookingRequest;
use App\Models\EscrowHold;
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
    public function releaseEscrow(EscrowHold $hold): void
    {
        $wasReleased = false;

        DB::transaction(function () use ($hold, &$wasReleased) {
            $fresh = EscrowHold::where('id', $hold->id)->lockForUpdate()->first();

            if (! $fresh || $fresh->status !== EscrowStatus::Held) {
                // Idempotency: already released or in another terminal state
                return;
            }

            $fresh->update([
                'status'      => EscrowStatus::Released->value,
                'released_at' => now(),
            ]);

            // Transition booking to Confirmed if still in Paid status
            $booking = BookingRequest::find($fresh->booking_request_id);

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

        $hold = EscrowHold::where('booking_request_id', $booking->id)
            ->where('status', EscrowStatus::Held->value)
            ->first();

        if (! $hold) {
            throw EscrowException::escrowNotHeld('not_found');
        }

        $this->releaseEscrow($hold);
    }
}
