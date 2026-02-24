<?php

namespace App\Listeners;

use App\Events\BookingDisputed;
use App\Jobs\SendPushNotification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyAdminOfBookingDisputed implements ShouldQueue
{
    public function handle(BookingDisputed $event): void
    {
        $booking = $event->booking;
        $data    = ['booking_id' => (string) $booking->id, 'type' => 'booking_disputed'];

        // Notify all admins via DB push notification (no FCM for admins in this listener)
        User::where('is_admin', true)->where('is_active', true)->each(function (User $admin) use ($booking, $data) {
            dispatch(new SendPushNotification(
                userId: $admin->id,
                title:  '⚠️ Litige signalé',
                body:   "Un litige a été ouvert sur la réservation #{$booking->id}.",
                data:   $data,
            ));
        });
    }
}
