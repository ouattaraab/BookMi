<?php

namespace App\Listeners;

use App\Events\BookingCreated;
use App\Jobs\SendPushNotification;
use App\Notifications\BookingRequestedNotification;
use App\Services\MessagingService;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyTalentOfNewBooking implements ShouldQueue
{
    public function __construct(private readonly MessagingService $messagingService)
    {
    }

    public function handle(BookingCreated $event): void
    {
        $booking = $event->booking->loadMissing([
            'talentProfile.user',
            'servicePackage',
            'client',
        ]);

        $talent = $booking->talentProfile?->user;
        if (! $talent) {
            return;
        }

        // Email
        $talent->notify(new BookingRequestedNotification($booking));

        // Push
        $clientName  = trim(($booking->client?->first_name ?? '') . ' ' . ($booking->client?->last_name ?? '')) ?: 'Un client';
        $packageName = $booking->servicePackage?->name ?? 'une prestation';

        SendPushNotification::dispatch(
            $talent->id,
            'Nouvelle demande de réservation',
            "{$clientName} souhaite réserver — {$packageName}",
            ['booking_id' => $booking->id, 'type' => 'booking_requested'],
        );

        // Auto-reply: create the booking conversation and send the talent's
        // welcome message immediately if auto-reply is configured and active.
        $this->messagingService->autoReplyOnBookingCreated($booking);
    }
}
