<?php

namespace App\Notifications;

use App\Models\ExperienceBooking;
use App\Models\PrivateExperience;
use App\Models\TalentProfile;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MeetAndGreetBookingConfirmation extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ExperienceBooking $booking,
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

        /** @var PrivateExperience|null $experience */
        $experience = $booking->experience instanceof PrivateExperience ? $booking->experience : null;

        /** @var User|null $client */
        $client = $booking->client instanceof User ? $booking->client : null;

        $clientName = $client !== null ? trim($client->first_name . ' ' . $client->last_name) : '';
        $clientName = $clientName !== '' ? $clientName : 'Participant';

        /** @var TalentProfile|null $talentProfile */
        $talentProfile = $experience?->talentProfile instanceof TalentProfile ? $experience->talentProfile : null;
        $talentName    = $talentProfile !== null ? $talentProfile->stage_name : "L'artiste";

        $eventDate = $experience !== null ? $experience->event_date->translatedFormat('l d F Y') : '—';
        $eventTime = $experience !== null ? $experience->event_date->format('H\hi') : '—';
        $eventTitle  = $experience !== null ? $experience->title : '—';
        $totalAmount = number_format($booking->total_amount, 0, ',', '.');
        $reference   = strtoupper('MG-' . str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT));

        // Le lieu est révélé si venue_revealed=true OU si la réservation est active
        $venueAddress = ($experience !== null && ($experience->venue_revealed || $booking->status->value !== 'cancelled'))
            ? $experience->venue_address
            : null;

        $detailUrl = $experience !== null ? url('/meet-and-greet/' . $experience->id) : url('/');

        return (new MailMessage())
            ->subject('Votre billet Meet & Greet — ' . $eventTitle)
            ->view('emails.meet-and-greet-ticket', [
                'clientName'   => $clientName,
                'talentName'   => $talentName,
                'eventTitle'   => $eventTitle,
                'eventDate'    => $eventDate,
                'eventTime'    => $eventTime,
                'venueAddress' => $venueAddress,
                'seatsCount'   => $booking->seats_count,
                'pricePerSeat' => number_format($booking->price_per_seat, 0, ',', '.'),
                'totalAmount'  => $totalAmount,
                'reference'    => $reference,
                'detailUrl'    => $detailUrl,
            ]);
    }
}
