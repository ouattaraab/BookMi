<?php

namespace App\Listeners;

use App\Events\BookingAccepted;
use App\Jobs\SendPushNotification;
use App\Notifications\BookingAcceptedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyClientOfBookingAccepted implements ShouldQueue
{
    public function handle(BookingAccepted $event): void
    {
        $booking = $event->booking->loadMissing([
            'client',
            'talentProfile.user',
            'servicePackage',
        ]);

        $client = $booking->client;
        if (! $client) {
            return;
        }

        // Email
        $client->notify(new BookingAcceptedNotification($booking));

        // Push
        $talentName  = $booking->talentProfile?->stage_name
            ?? trim(($booking->talentProfile?->user?->first_name ?? '') . ' ' . ($booking->talentProfile?->user?->last_name ?? ''))
            ?: 'Le talent';
        $amount = number_format($booking->total_amount ?? 0, 0, ',', ' ');

        SendPushNotification::dispatch(
            $client->id,
            'Réservation acceptée !',
            "{$talentName} a accepté votre demande — {$amount} XOF à payer",
            ['booking_id' => $booking->id, 'type' => 'booking_accepted'],
        );
    }
}
