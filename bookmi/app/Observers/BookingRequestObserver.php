<?php

namespace App\Observers;

use App\Models\BookingRequest;
use App\Services\ActivityLogger;

class BookingRequestObserver
{
    public function created(BookingRequest $booking): void
    {
        ActivityLogger::log('booking.requested', $booking, [
            'talent_profile_id' => $booking->talent_profile_id,
            'event_date'        => $booking->event_date instanceof \Carbon\Carbon
                ? $booking->event_date->toDateString()
                : (string) $booking->event_date,
            'event_location'    => $booking->event_location,
            'total_amount'      => $booking->total_amount,
        ]);
    }

    public function updated(BookingRequest $booking): void
    {
        if (! $booking->wasChanged('status')) {
            return;
        }

        ActivityLogger::log('booking.status_changed', $booking, [
            'old_status'        => $booking->getOriginal('status'),
            'new_status'        => $booking->status,
            'talent_profile_id' => $booking->talent_profile_id,
        ]);
    }
}
