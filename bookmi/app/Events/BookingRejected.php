<?php

namespace App\Events;

use App\Models\BookingRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingRejected
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly BookingRequest $booking,
    ) {
    }
}
