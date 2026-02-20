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
        $transaction = $this->transaction;
        $booking     = $transaction->bookingRequest;

        $talentName  = $booking?->talentProfile?->stage_name
            ?? trim(($booking?->talentProfile?->user?->first_name ?? '') . ' ' . ($booking?->talentProfile?->user?->last_name ?? ''))
            ?: 'Talent';
        $clientName  = trim(($booking?->client?->first_name ?? '') . ' ' . ($booking?->client?->last_name ?? '')) ?: 'Client';
        $packageName = $booking?->servicePackage?->name ?? '—';
        $eventDate   = $booking?->event_date?->translatedFormat('d F Y') ?? '—';
        $amount      = number_format($transaction->amount ?? 0, 0, ',', ' ');
        $reference   = $transaction->idempotency_key ?? $transaction->gateway_reference ?? '—';

        return (new MailMessage())
            ->subject('Paiement reçu et sécurisé — BookMi')
            ->markdown('emails.payment-received', [
                'talentName'    => $talentName,
                'clientName'    => $clientName,
                'packageName'   => $packageName,
                'eventDate'     => $eventDate,
                'escrowAmount'  => $amount,
                'reference'     => $reference,
                'actionUrl'     => url('/talent/bookings/' . ($booking?->id ?? '')),
            ]);
    }
}
