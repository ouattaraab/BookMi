<?php

namespace App\Notifications;

use App\Models\BookingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCancelledNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly BookingRequest $booking,
        private readonly string         $cancelledByRole, // 'client' | 'talent'
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $eventDate   = $this->booking->event_date?->translatedFormat('d F Y') ?? '—';
        $packageName = $this->booking->servicePackage?->name ?? '—';

        $who = $this->cancelledByRole === 'client' ? 'Le client' : 'Le talent';

        return (new MailMessage())
            ->subject('Réservation annulée — BookMi')
            ->greeting('Information importante')
            ->line("{$who} a annulé la réservation suivante :")
            ->line("**Prestation :** {$packageName}")
            ->line("**Date prévue :** {$eventDate}")
            ->action('Voir les détails', url('/client/bookings/' . $this->booking->id))
            ->line('Si vous avez des questions, contactez notre support.')
            ->salutation("L'équipe BookMi");
    }
}
