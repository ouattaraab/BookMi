<?php

namespace App\Listeners;

use App\Events\BookingCompleted;
use App\Jobs\SendPushNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyPartiesOfBookingCompleted implements ShouldQueue
{
    public function handle(BookingCompleted $event): void
    {
        $booking    = $event->booking;
        $client     = $booking->client;
        $talent     = $booking->talentProfile;
        $talentName = $talent->display_name ?? ($talent->user->first_name ?? 'Votre talent');
        $data       = ['booking_id' => (string) $booking->id, 'type' => 'booking_completed'];

        // Notify client
        dispatch(new SendPushNotification(
            userId: $client->id,
            title:  'Prestation terminée !',
            body:   "Merci d'avoir utilisé {$talentName}. Laissez un avis !",
            data:   $data,
        ));

        // Notify talent
        if ($talent->user_id) {
            dispatch(new SendPushNotification(
                userId: $talent->user_id,
                title:  'Prestation terminée !',
                body:   'Merci pour votre prestation. Votre évaluation arrive bientôt.',
                data:   $data,
            ));
        }
    }
}
