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
        $booking          = $this->booking;
        $packageName      = $booking->servicePackage?->name ?? '—';
        $eventDate        = $booking->event_date?->translatedFormat('d F Y') ?? '—';
        $cancelledByLabel = $this->cancelledByRole === 'client' ? 'Le client' : 'Le talent';

        $recipientName = trim(($notifiable->first_name ?? '') . ' ' . ($notifiable->last_name ?? '')) ?: 'Utilisateur';

        // Show refund info only if client cancelled after payment
        $refundInfo = null;
        if ($this->cancelledByRole === 'client' && in_array($booking->status?->value ?? $booking->status, ['paid', 'confirmed'])) {
            $refundInfo = 'Si un paiement a été effectué, le remboursement sera traité sous 5 à 10 jours ouvrés selon la politique d\'annulation applicable.';
        }

        return (new MailMessage())
            ->subject('Réservation annulée — BookMi')
            ->markdown('emails.booking-cancelled', [
                'recipientName'    => $recipientName,
                'packageName'      => $packageName,
                'eventDate'        => $eventDate,
                'cancelledByLabel' => $cancelledByLabel,
                'refundInfo'       => $refundInfo,
                'actionUrl'        => url('/client/bookings/' . $booking->id),
            ]);
    }
}
