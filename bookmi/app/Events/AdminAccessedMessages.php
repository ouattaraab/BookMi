<?php

namespace App\Events;

use App\Models\BookingRequest;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdminAccessedMessages
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly User $admin,
        public readonly BookingRequest $booking,
    ) {
    }
}
