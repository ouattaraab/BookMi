<?php

namespace App\Policies;

use App\Models\BookingRequest;
use App\Models\TalentProfile;
use App\Models\User;

class BookingRequestPolicy
{
    public function view(User $user, BookingRequest $booking): bool
    {
        return $booking->isOwnedByUser($user);
    }

    public function accept(User $user, BookingRequest $booking): bool
    {
        return $this->isTalentOwner($user, $booking);
    }

    public function reject(User $user, BookingRequest $booking): bool
    {
        return $this->isTalentOwner($user, $booking);
    }

    private function isTalentOwner(User $user, BookingRequest $booking): bool
    {
        return TalentProfile::where('id', $booking->talent_profile_id)
            ->where('user_id', $user->id)
            ->exists();
    }
}
