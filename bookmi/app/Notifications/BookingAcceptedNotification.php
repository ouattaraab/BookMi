<?php

namespace App\Notifications;

use App\Models\BookingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingAcceptedNotification extends Notification
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
        $talentName  = $this->booking->talentProfile?->stage_name
            ?? trim(($this->booking->talentProfile?->user?->first_name ?? '') . ' ' . ($this->booking->talentProfile?->user?->last_name ?? ''))
            ?: 'Le talent';
        $eventDate   = $this->booking->event_date?->translatedFormat('d F Y') ?? '—';
        $packageName = $this->booking->servicePackage?->name ?? '—';
        $amount      = number_format($this->booking->total_amount ?? 0, 0, ',', ' ');

        return (new MailMessage())
            ->subject('Votre réservation a été acceptée — BookMi')
            ->greeting('Bonne nouvelle !')
            ->line("**{$talentName}** a accepté votre demande de prestation.")
            ->line("**Prestation :** {$packageName}")
            ->line("**Date :** {$eventDate}")
            ->line("**Total à payer :** {$amount} XOF")
            ->action('Procéder au paiement', url('/client/bookings/' . $this->booking->id . '/pay'))
            ->line('Finalisez votre réservation en effectuant le paiement sécurisé.')
            ->salutation("L'équipe BookMi");
    }
}
