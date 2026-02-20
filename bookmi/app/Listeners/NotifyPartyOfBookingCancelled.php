<?php

namespace App\Listeners;

use App\Events\BookingCancelled;
use App\Jobs\SendPushNotification;
use App\Notifications\BookingCancelledNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyPartyOfBookingCancelled implements ShouldQueue
{
    public function handle(BookingCancelled $event): void
    {
        $booking = $event->booking->loadMissing([
            'client',
            'talentProfile.user',
            'servicePackage',
        ]);

        $cancelledBy = $booking->cancelled_by ?? 'client'; // field or fallback

        // Notify the OTHER party
        if ($cancelledBy === 'talent') {
            $recipient      = $booking->client;
            $notifyRole     = 'talent';
            $pushTitle      = 'Réservation annulée';
            $packageName    = $booking->servicePackage?->name ?? 'prestation';
            $pushBody       = "Le talent a annulé la réservation — {$packageName}";
        } else {
            $recipient      = $booking->talentProfile?->user;
            $notifyRole     = 'client';
            $pushTitle      = 'Réservation annulée';
            $packageName    = $booking->servicePackage?->name ?? 'prestation';
            $pushBody       = "Le client a annulé la réservation — {$packageName}";
        }

        if (! $recipient) {
            return;
        }

        // Email
        $recipient->notify(new BookingCancelledNotification($booking, $notifyRole));

        // Push
        SendPushNotification::dispatch(
            $recipient->id,
            $pushTitle,
            $pushBody,
            ['booking_id' => $booking->id, 'type' => 'booking_cancelled'],
        );
    }
}
