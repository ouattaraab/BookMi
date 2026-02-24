<?php

namespace App\Listeners;

use App\Events\BookingRejected;
use App\Jobs\SendPushNotification;
use App\Notifications\BookingRejectedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyClientOfBookingRejected implements ShouldQueue
{
    public function handle(BookingRejected $event): void
    {
        $booking = $event->booking;
        $client  = $booking->client;
        $talent  = $booking->talentProfile;
        $talentName = $talent->display_name ?? ($talent->user->first_name ?? 'Le talent');

        // Email
        $client->notify(new BookingRejectedNotification($booking));

        // Push
        dispatch(new SendPushNotification(
            userId: $client->id,
            title:  'Demande de réservation refusée',
            body:   "{$talentName} n'est pas disponible pour votre demande.",
            data:   ['booking_id' => (string) $booking->id, 'type' => 'booking_rejected'],
        ));
    }
}
