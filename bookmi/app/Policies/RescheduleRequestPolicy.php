<?php

namespace App\Policies;

use App\Models\BookingRequest;
use App\Models\RescheduleRequest;
use App\Models\User;

class RescheduleRequestPolicy
{
    /**
     * Either party (client or talent) may create a reschedule request.
     */
    public function create(User $user, BookingRequest $booking): bool
    {
        return $booking->isOwnedByUser($user);
    }

    /**
     * Only the counterparty (not the requester) may accept or reject.
     */
    public function respond(User $user, RescheduleRequest $reschedule): bool
    {
        return $reschedule->booking->isOwnedByUser($user)
            && $reschedule->requested_by_id !== $user->id;
    }
}
