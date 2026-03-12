<?php

namespace App\Notifications;

use App\Models\BookingRequest;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EscrowReleasedAdminTalentNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly BookingRequest $booking,
        private readonly float $cachetAmount,
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
            ?: 'Talent';

        /** @var \App\Models\User|null $client */
        $client     = $booking->client;
        $clientName = trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? '')) ?: 'Client';
        $eventDate  = $booking->event_date
            ? Carbon::parse($booking->event_date)->translatedFormat('d F Y')
            : '—';
        $amount = number_format($this->cachetAmount, 0, ',', ' ');

        return (new MailMessage())
            ->subject('Vos fonds ont été libérés — BookMi')
            ->markdown('emails.escrow-released-admin-talent', [
                'talentName' => $talentName,
                'clientName' => $clientName,
                'eventDate'  => $eventDate,
                'amount'     => $amount,
                'actionUrl'  => url('/talent/bookings/' . $booking->id),
            ]);
    }
}
