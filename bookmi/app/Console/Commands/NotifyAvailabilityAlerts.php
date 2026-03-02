<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Jobs\SendPushNotification;
use App\Models\AvailabilityAlert;
use App\Models\BookingRequest;
use Illuminate\Console\Command;

class NotifyAvailabilityAlerts extends Command
{
    protected $signature = 'bookmi:notify-availability-alerts {--dry-run : Log only, do not notify}';

    protected $description = 'Check pending availability alerts and notify users when a talent slot is free.';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $notified = 0;

        AvailabilityAlert::with(['user', 'talentProfile'])
            ->whereNull('notified_at')
            ->where('event_date', '>=', today())
            ->chunkById(100, function ($alerts) use ($isDryRun, &$notified) {
                foreach ($alerts as $alert) {
                    if (! $alert->user || ! $alert->talentProfile) {
                        continue;
                    }

                    // Check whether the slot is now free
                    $isBusy = BookingRequest::where('talent_profile_id', $alert->talent_profile_id)
                        ->whereIn('status', [
                            BookingStatus::Pending->value,
                            BookingStatus::Accepted->value,
                            BookingStatus::Paid->value,
                            BookingStatus::Confirmed->value,
                        ])
                        ->whereDate('event_date', $alert->event_date)
                        ->exists();

                    if ($isBusy) {
                        continue;
                    }

                    $stageName = $alert->talentProfile->stage_name ?? 'Le talent';
                    $date      = \Carbon\Carbon::parse($alert->event_date);
                    $dateLabel = $date->translatedFormat('d M Y');

                    $this->line(
                        "Alert #{$alert->id} — user #{$alert->user_id} ← {$stageName} disponible le {$dateLabel}"
                        . ($isDryRun ? ' [DRY-RUN]' : ''),
                    );

                    if (! $isDryRun) {
                        SendPushNotification::dispatch(
                            userId: $alert->user_id,
                            title: "{$stageName} est disponible ! 🎉",
                            body: "{$stageName} a un créneau libre le {$dateLabel}. Réservez maintenant avant qu'il ne soit pris.",
                            data: [
                                'type'              => 'availability_alert',
                                'talent_profile_id' => (string) $alert->talent_profile_id,
                                'event_date'        => $date->toDateString(),
                            ],
                        );

                        $alert->update(['notified_at' => now()]);
                    }

                    $notified++;
                }
            });

        $this->info("{$notified} alert(s) " . ($isDryRun ? 'would be ' : '') . 'dispatched.');

        return self::SUCCESS;
    }
}
