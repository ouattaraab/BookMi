<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Jobs\SendPushNotification;
use App\Models\BookingRequest;
use App\Models\TalentProfile;
use Illuminate\Console\Command;

class DetectEmptyCalendar extends Command
{
    protected $signature = 'bookmi:detect-empty-calendar {--dry-run : Log only, do not notify}';

    protected $description = 'Detect talents with no upcoming bookings in the next 30 days and notify them.';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $notified = 0;

        TalentProfile::with('user')
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->chunkById(50, function ($talents) use ($isDryRun, &$notified) {
                foreach ($talents as $talent) {
                    $upcomingBookings = BookingRequest::where('talent_profile_id', $talent->id)
                        ->whereIn('status', [
                            BookingStatus::Pending->value,
                            BookingStatus::Accepted->value,
                            BookingStatus::Paid->value,
                            BookingStatus::Confirmed->value,
                        ])
                        ->where('event_date', '>=', today())
                        ->where('event_date', '<=', today()->addDays(30))
                        ->count();

                    if ($upcomingBookings > 0) {
                        continue;
                    }

                    // Rate-limit: only once per 7 days
                    if (
                        $talent->calendar_empty_notified_at
                        && $talent->calendar_empty_notified_at->greaterThan(now()->subDays(7))
                    ) {
                        continue;
                    }

                    $this->line(
                        "Talent #{$talent->id} ({$talent->stage_name}): calendrier vide dans les 30 prochains jours"
                        . ($isDryRun ? ' [DRY-RUN]' : ''),
                    );

                    if (! $isDryRun) {
                        if ($talent->user->fcm_token) {
                            SendPushNotification::dispatch(
                                userId: $talent->user->id,
                                title: 'Calendrier vide 📅',
                                body: 'Vous n\'avez aucune réservation dans les 30 prochains jours. Boostez votre visibilité !',
                                data: ['type' => 'empty_calendar'],
                            );
                        }

                        $talent->update(['calendar_empty_notified_at' => now()]);
                    }

                    $notified++;
                }
            });

        $this->info("{$notified} talent(s) " . ($isDryRun ? 'would be ' : '') . 'notified.');

        return self::SUCCESS;
    }
}
