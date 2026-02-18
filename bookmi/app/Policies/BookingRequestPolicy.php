<?php

namespace App\Policies;

use App\Models\BookingRequest;
use App\Models\User;

class BookingRequestPolicy
{
    public function view(User $user, BookingRequest $booking): bool
    {
        return $booking->isOwnedByUser($user);
    }
}
