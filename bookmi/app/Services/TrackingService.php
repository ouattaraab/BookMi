<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\TrackingStatus;
use App\Events\TrackingStatusChanged;
use App\Jobs\SendPushNotification;
use App\Models\BookingRequest;
use App\Models\TrackingEvent;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TrackingService
{
    /**
     * Record a new tracking status update for a booking.
     *
     * Rules:
     *  - Booking must be in Paid or Confirmed status (i.e., event day is active).
     *  - Status must be a valid forward-only transition from the last recorded status.
     *  - If no previous event exists, first allowed status is 'preparing'.
     *
     * @throws ValidationException
     */
    public function sendUpdate(
        BookingRequest $booking,
        User $talent,
        TrackingStatus $status,
        ?float $latitude = null,
        ?float $longitude = null,
    ): TrackingEvent {
        $this->assertBookingIsActive($booking);
        $this->assertValidTransition($booking, $status);

        $event = TrackingEvent::create([
            'booking_request_id' => $booking->id,
            'updated_by'         => $talent->id,
            'status'             => $status,
            'latitude'           => $latitude,
            'longitude'          => $longitude,
            'occurred_at'        => now(),
        ]);

        try {
            broadcast(new TrackingStatusChanged($event));
        } catch (\Throwable $e) {
            Log::error('TrackingStatusChanged broadcast failed', [
                'tracking_event_id' => $event->id,
                'error'             => $e->getMessage(),
            ]);
        }

        // Push notification to client
        $this->notifyClient($booking, $event, $status);

        return $event;
    }

    /**
     * Send a push notification to the client and record the notification timestamp.
     */
    private function notifyClient(BookingRequest $booking, TrackingEvent $event, TrackingStatus $status): void
    {
        $client = $booking->client;
        if (! $client) {
            return;
        }

        $message = $this->clientPushMessage($status);
        if ($message === null) {
            return;
        }

        SendPushNotification::dispatch(
            $client->id,
            $message['title'],
            $message['body'],
            [
                'type'            => 'tracking_update',
                'booking_id'      => (string) $booking->id,
                'tracking_status' => $status->value,
            ],
        );

        $event->update(['client_notified_at' => now()]);
    }

    /**
     * Returns the push message for a given tracking status, or null if no notification needed.
     *
     * @return array{title: string, body: string}|null
     */
    private function clientPushMessage(TrackingStatus $status): ?array
    {
        return match ($status) {
            TrackingStatus::Preparing  => [
                'title' => 'Votre artiste se prépare 🎵',
                'body'  => 'Votre artiste se prépare pour votre événement.',
            ],
            TrackingStatus::EnRoute    => [
                'title' => 'Votre artiste est en route 🚗',
                'body'  => 'Votre artiste est en route vers votre événement.',
            ],
            TrackingStatus::Arrived    => [
                'title' => 'Votre artiste est arrivé ! ✅',
                'body'  => 'Votre artiste est arrivé ! Confirmez sa présence pour libérer le paiement.',
            ],
            TrackingStatus::Performing => [
                'title' => 'La prestation a commencé ! 🎤',
                'body'  => 'La prestation a commencé !',
            ],
            TrackingStatus::Completed  => [
                'title' => 'Prestation terminée ⭐',
                'body'  => 'Prestation terminée. Laissez un avis !',
            ],
            default => null,
        };
    }

    /**
     * @throws ValidationException
     */
    private function assertBookingIsActive(BookingRequest $booking): void
    {
        $allowed = [BookingStatus::Paid, BookingStatus::Confirmed];

        if (! in_array($booking->status, $allowed, strict: true)) {
            throw ValidationException::withMessages([
                'booking' => 'Booking must be in paid or confirmed status to enable tracking.',
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    private function assertValidTransition(BookingRequest $booking, TrackingStatus $next): void
    {
        /** @var TrackingEvent|null $last */
        $last = TrackingEvent::where('booking_request_id', $booking->id)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->first();

        if ($last === null) {
            // First update must be 'preparing'
            if ($next !== TrackingStatus::Preparing) {
                throw ValidationException::withMessages([
                    'status' => 'First tracking status must be "preparing".',
                ]);
            }

            return;
        }

        if (! $last->status->canTransitionTo($next)) {
            throw ValidationException::withMessages([
                'status' => "Invalid status transition: {$last->status->value} → {$next->value}.",
            ]);
        }
    }
}
