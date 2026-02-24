<?php

namespace App\Notifications;

use App\Models\BookingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly BookingRequest $booking,
    ) {
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking     = $this->booking;
        $packageName = $booking->servicePackage?->name ?? '—';
        $eventDate   = $booking->event_date?->translatedFormat('d F Y') ?? '—';
        $talentName  = $booking->talentProfile?->display_name
            ?? $booking->talentProfile?->user?->first_name
            ?? 'Le talent';

        $recipientName = trim(($notifiable->first_name ?? '') . ' ' . ($notifiable->last_name ?? '')) ?: 'Utilisateur';

        return (new MailMessage())
            ->subject('Demande de réservation refusée — BookMi')
            ->markdown('emails.booking-rejected', [
                'recipientName' => $recipientName,
                'packageName'   => $packageName,
                'eventDate'     => $eventDate,
                'talentName'    => $talentName,
                'actionUrl'     => url('/client/bookings'),
            ]);
    }
}
