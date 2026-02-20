<?php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Transaction $transaction,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking     = $this->transaction->bookingRequest;
        $amount      = number_format($this->transaction->amount ?? 0, 0, ',', ' ');
        $eventDate   = $booking?->event_date?->translatedFormat('d F Y') ?? '—';
        $packageName = $booking?->servicePackage?->name ?? '—';
        $ref         = $this->transaction->idempotency_key ?? $this->transaction->gateway_reference ?? '—';

        return (new MailMessage())
            ->subject('Paiement reçu et sécurisé — BookMi')
            ->greeting('Bonne nouvelle !')
            ->line("Le paiement de **{$amount} XOF** a été reçu et placé en séquestre.")
            ->line("**Prestation :** {$packageName}")
            ->line("**Date :** {$eventDate}")
            ->line("**Référence :** `{$ref}`")
            ->line('Le montant vous sera versé après confirmation de la prestation.')
            ->action('Voir la réservation', url('/talent/bookings/' . ($booking?->id ?? '')))
            ->salutation("L'équipe BookMi");
    }
}
