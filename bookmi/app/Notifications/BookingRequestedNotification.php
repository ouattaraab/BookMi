<?php

namespace App\Notifications;

use App\Models\BookingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly BookingRequest $booking,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking     = $this->booking;
        $talentName  = $booking->talentProfile?->stage_name
            ?? trim(($booking->talentProfile?->user?->first_name ?? '') . ' ' . ($booking->talentProfile?->user?->last_name ?? ''))
            ?: 'Talent';
        $clientName  = trim(($booking->client?->first_name ?? '') . ' ' . ($booking->client?->last_name ?? '')) ?: 'Un client';
        $packageName = $booking->servicePackage?->name ?? '—';
        $eventDate   = $booking->event_date?->translatedFormat('d F Y') ?? '—';
        $eventLocation = $booking->event_location ?? '—';
        $amount      = number_format($booking->total_amount ?? 0, 0, ',', ' ');

        return (new MailMessage())
            ->subject('Nouvelle demande de réservation — BookMi')
            ->markdown('emails.booking-requested', [
                'talentName'    => $talentName,
                'clientName'    => $clientName,
                'packageName'   => $packageName,
                'eventDate'     => $eventDate,
                'eventLocation' => $eventLocation,
                'amount'        => $amount,
                'message'       => $booking->message ?? null,
                'actionUrl'     => url('/talent/bookings/' . $booking->id),
            ]);
    }
}
