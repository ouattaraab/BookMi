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
        $talentName  = $booking->talentProfile?->stage_name
            ?? trim(($booking->talentProfile?->user?->first_name ?? '') . ' ' . ($booking->talentProfile?->user?->last_name ?? ''))
            ?: 'Le talent';
        $clientName  = trim(($booking->client?->first_name ?? '') . ' ' . ($booking->client?->last_name ?? '')) ?: 'Client';
        $packageName = $booking->servicePackage?->name ?? '—';
        $eventDate   = $booking->event_date?->translatedFormat('d F Y') ?? '—';

        $artistFee     = number_format($booking->cachet_amount ?? ($booking->total_amount ?? 0), 0, ',', ' ');
        $commission    = number_format($booking->commission_amount ?? 0, 0, ',', ' ');
        $total         = number_format($booking->total_amount ?? 0, 0, ',', ' ');
        $talentComment = $booking->accept_comment ?? null;

        return (new MailMessage())
            ->subject('Votre réservation est acceptée — BookMi')
            ->markdown('emails.booking-accepted', [
                'clientName'    => $clientName,
                'talentName'    => $talentName,
                'packageName'   => $packageName,
                'eventDate'     => $eventDate,
                'artistFee'     => $artistFee,
                'commission'    => $commission,
                'total'         => $total,
                'talentComment' => $talentComment,
                'actionUrl'     => url('/client/bookings/' . $booking->id . '/pay'),
            ]);
    }
}
