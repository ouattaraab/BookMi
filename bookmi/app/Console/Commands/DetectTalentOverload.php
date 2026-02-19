<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Jobs\SendPushNotification;
use App\Models\BookingRequest;
use App\Models\TalentProfile;
use Illuminate\Console\Command;

class DetectTalentOverload extends Command
{
    protected $signature = 'bookmi:detect-talent-overload {--dry-run : Log only, do not notify}';

    protected $description = 'Detect overloaded talents and notify their managers via push notification.';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $notified = 0;

        TalentProfile::with(['managers'])
            ->whereHas('managers')
            ->chunkById(50, function ($talents) use ($isDryRun, &$notified) {
                foreach ($talents as $talent) {
                    $activeBookings = BookingRequest::where('talent_profile_id', $talent->id)
                        ->whereIn('status', [
                            BookingStatus::Confirmed->value,
                            BookingStatus::Paid->value,
                        ])
                        ->count();

                    if ($activeBookings < $talent->overload_threshold) {
                        continue;
                    }

                    // Avoid notification spam: only notify once per day
                    if (
                        $talent->overload_notified_at
                        && $talent->overload_notified_at->isToday()
                    ) {
                        continue;
                    }

                    $this->line(
                        "Talent #{$talent->id} ({$talent->stage_name}): "
                        . "{$activeBookings} bookings actifs ≥ seuil {$talent->overload_threshold}"
                        . ($isDryRun ? ' [DRY-RUN]' : ''),
                    );

                    if (! $isDryRun) {
                        foreach ($talent->managers as $manager) {
                            if ($manager->fcm_token) {
                                SendPushNotification::dispatch(
                                    userId: $manager->id,
                                    title: "⚠️ Surcharge — {$talent->stage_name}",
                                    body: "{$talent->stage_name} a {$activeBookings} réservations actives "
                                        . "(seuil : {$talent->overload_threshold}).",
                                    data: ['talent_profile_id' => (string) $talent->id],
                                );
                            }
                        }

                        $talent->update(['overload_notified_at' => now()]);
                    }

                    $notified++;
                }
            });

        $this->info("{$notified} talent(s) " . ($isDryRun ? 'would be ' : '') . 'marked as overloaded.');

        return self::SUCCESS;
    }
}
