<?php

namespace App\Notifications;

use App\Models\BookingRequest;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EscrowReleasedAdminClientNotification extends Notification
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
        $booking = $this->booking;

        /** @var \App\Models\TalentProfile|null $talentProfile */
        $talentProfile = $booking->talentProfile;
        $talentName    = $talentProfile?->stage_name
            ?? trim(($talentProfile?->user?->first_name ?? '') . ' ' . ($talentProfile?->user?->last_name ?? ''))
            ?: 'Le talent';

        /** @var \App\Models\User|null $client */
        $client     = $booking->client;
        $clientName = trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? '')) ?: 'Client';
        $eventDate  = $booking->event_date
            ? Carbon::parse($booking->event_date)->translatedFormat('d F Y')
            : '—';
        $amount = number_format($booking->cachet_amount ?? 0, 0, ',', ' ');

        return (new MailMessage())
            ->subject('Fonds libérés par l\'administrateur — BookMi')
            ->markdown('emails.escrow-released-admin-client', [
                'clientName' => $clientName,
                'talentName' => $talentName,
                'eventDate'  => $eventDate,
                'amount'     => $amount,
                'actionUrl'  => url('/client/bookings/' . $booking->id),
            ]);
    }
}
