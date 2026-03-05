<?php

namespace App\Notifications;

use App\Models\BookingRequest;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly BookingRequest $booking,
        private readonly string $label, // 'J-7' | 'J-2'
        private readonly string $role,  // 'client' | 'talent'
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
        $talentProfile = $booking->talentProfile;
        $client        = $booking->client;
        $servicePackage = $booking->servicePackage;
        $talentName  = $talentProfile ? $talentProfile->stage_name : 'votre talent';
        $firstName   = $client ? $client->first_name : '';
        $lastName    = $client ? $client->last_name : '';
        $clientName  = trim($firstName . ' ' . $lastName) ?: 'le client';
        $rawDate     = $booking->event_date;
        $eventDate   = $rawDate ? Carbon::parse((string) $rawDate)->translatedFormat('d F Y') : '—';
        $location    = $booking->event_location ?? '—';
        $packageName = $servicePackage ? $servicePackage->name : '—';

        $isClient   = $this->role === 'client';
        $otherParty = $isClient ? $talentName : $clientName;
        $actionUrl  = $isClient
            ? url('/client/bookings/' . $booking->id)
            : url('/talent/bookings/' . $booking->id);

        return (new MailMessage())
            ->subject("Rappel {$this->label} — Prestation à venir — BookMi")
            ->markdown('emails.booking-reminder', [
                'label'       => $this->label,
                'role'        => $this->role,
                'otherParty'  => $otherParty,
                'packageName' => $packageName,
                'eventDate'   => $eventDate,
                'location'    => $location,
                'actionUrl'   => $actionUrl,
            ]);
    }
}
