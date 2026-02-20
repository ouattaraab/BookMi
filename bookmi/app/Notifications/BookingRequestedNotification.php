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
        $clientName  = trim(($this->booking->client->first_name ?? '') . ' ' . ($this->booking->client->last_name ?? '')) ?: 'Un client';
        $eventDate   = $this->booking->event_date?->translatedFormat('d F Y') ?? '—';
        $packageName = $this->booking->servicePackage?->name ?? '—';
        $amount      = number_format($this->booking->total_amount ?? 0, 0, ',', ' ');

        return (new MailMessage())
            ->subject('Nouvelle demande de réservation — BookMi')
            ->greeting('Bonjour !')
            ->line("**{$clientName}** vient de faire une demande de prestation.")
            ->line("**Prestation :** {$packageName}")
            ->line("**Date :** {$eventDate}")
            ->line("**Montant :** {$amount} XOF")
            ->action('Voir la demande', url('/talent/bookings/' . $this->booking->id))
            ->line('Acceptez ou refusez la demande depuis votre espace talent.')
            ->salutation("L'équipe BookMi");
    }
}
