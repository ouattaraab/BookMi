<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Enums\TrackingStatus;
use App\Jobs\SendPushNotification;
use App\Models\BookingRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendMissingCheckinAlerts extends Command
{
    protected $signature = 'bookmi:missing-checkin-alerts {--dry-run : Log only, do not dispatch jobs}';

    protected $description = 'Alert clients and talents when no check-in has been recorded for today\'s events.';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $today    = Carbon::today()->toDateString();

        $alertCount = 0;

        BookingRequest::with(['client', 'talentProfile.user'])
            ->whereIn('status', [
                BookingStatus::Paid->value,
                BookingStatus::Confirmed->value,
            ])
            ->whereDate('event_date', $today)
            ->chunkById(100, function ($bookings) use ($isDryRun, &$alertCount) {
                foreach ($bookings as $booking) {
                    // Skip if any 'arrived' tracking event exists (check-in done)
                    $hasCheckedIn = $booking->trackingEvents()
                        ->where('status', TrackingStatus::Arrived->value)
                        ->exists();

                    if ($hasCheckedIn) {
                        continue;
                    }

                    $eventDate = $booking->event_date->format('d/m/Y');

                    // Alert client
                    if ($booking->client_id && ! $isDryRun) {
                        SendPushNotification::dispatch(
                            userId: $booking->client_id,
                            title: 'Vérification de prestation',
                            body: "Le talent n'a pas encore enregistré son arrivée pour votre prestation du {$eventDate}.",
                            data: ['booking_id' => (string) $booking->id, 'alert_type' => 'missing_checkin'],
                        );
                    }

                    // Alert talent
                    $talentUserId = $booking->talentProfile?->user_id;
                    if ($talentUserId && ! $isDryRun) {
                        SendPushNotification::dispatch(
                            userId: $talentUserId,
                            title: 'Check-in manquant',
                            body: "Vous n'avez pas encore enregistré votre arrivée pour la prestation du {$eventDate}. Effectuez votre check-in.",
                            data: ['booking_id' => (string) $booking->id, 'alert_type' => 'missing_checkin'],
                        );
                    }

                    $this->line("Missing check-in: Booking #{$booking->id} on {$eventDate}"
                        . ($isDryRun ? ' [DRY-RUN]' : ''));

                    $alertCount++;
                }
            });

        $this->info("Missing check-in alerts: {$alertCount} booking(s) notified.");

        return self::SUCCESS;
    }
}
