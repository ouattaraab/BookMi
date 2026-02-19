<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Jobs\SendPushNotification;
use App\Models\BookingRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendReminderNotifications extends Command
{
    protected $signature = 'bookmi:send-reminders {--dry-run : Log only, do not dispatch jobs}';

    protected $description = 'Dispatch push notifications reminders J-7 and J-2 before bookings.';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $today    = Carbon::today();

        $windows = [
            'J-7' => $today->copy()->addDays(7)->toDateString(),
            'J-2' => $today->copy()->addDays(2)->toDateString(),
        ];

        $totalDispatched = 0;

        foreach ($windows as $label => $targetDate) {
            $count = 0;

            BookingRequest::with(['client', 'talentProfile'])
                ->whereIn('status', [
                    BookingStatus::Paid->value,
                    BookingStatus::Confirmed->value,
                ])
                ->whereDate('event_date', $targetDate)
                ->chunkById(100, function ($bookings) use ($label, $isDryRun, &$count) {
                    foreach ($bookings as $booking) {
                        $clientName = $booking->client?->first_name ?? 'Client';
                        $talentName = $booking->talentProfile?->stage_name ?? 'talent';
                        $eventDate  = $booking->event_date->format('d/m/Y');

                        $title = "Rappel {$label} — Prestation à venir";
                        $body  = "Votre prestation avec {$talentName} est prévue le {$eventDate}.";
                        $data  = ['booking_id' => (string) $booking->id];

                        if (! $isDryRun && $booking->client_id) {
                            SendPushNotification::dispatch(
                                userId: $booking->client_id,
                                title: $title,
                                body: $body,
                                data: $data,
                            );
                        }

                        $this->line("[{$label}] Booking #{$booking->id} → user #{$booking->client_id} ({$eventDate})"
                            . ($isDryRun ? ' [DRY-RUN]' : ''));

                        $count++;
                    }
                });

            $this->info("{$label}: {$count} reminder(s) dispatched for {$targetDate}.");
            $totalDispatched += $count;
        }

        $this->info("Total reminders: {$totalDispatched}.");

        return self::SUCCESS;
    }
}
