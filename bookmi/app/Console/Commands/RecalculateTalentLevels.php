<?php

namespace App\Console\Commands;

use App\Enums\TalentLevel;
use App\Jobs\SendPushNotification;
use App\Models\TalentProfile;
use Illuminate\Console\Command;

class RecalculateTalentLevels extends Command
{
    protected $signature = 'bookmi:recalculate-talent-levels {--dry-run : Log only, do not save}';

    protected $description = 'Recalculate talent levels based on total_bookings and average_rating.';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $levels = config('bookmi.talent.levels');
        $updated = 0;

        // Iterate from highest to lowest level
        $sortedLevels = collect($levels)
            ->sortByDesc('min_bookings')
            ->all();

        TalentProfile::chunkById(100, function ($talents) use ($sortedLevels, $isDryRun, &$updated) {
            foreach ($talents as $talent) {
                $newLevel = TalentLevel::NOUVEAU;

                foreach ($sortedLevels as $levelName => $thresholds) {
                    if (
                        $talent->total_bookings >= $thresholds['min_bookings']
                        && (float) $talent->average_rating >= $thresholds['min_rating']
                    ) {
                        $newLevel = TalentLevel::from($levelName);
                        break;
                    }
                }

                if ($talent->talent_level !== $newLevel) {
                    // Determine if this is a promotion (upgrade) or demotion.
                    // Use config array to avoid PHPStan issues with enum casts.
                    $oldLevelValue   = is_string($talent->talent_level)
                        ? $talent->talent_level
                        : ($talent->talent_level instanceof TalentLevel ? $talent->talent_level->value : 'nouveau');
                    $oldMinBookings  = (int) ($sortedLevels[$oldLevelValue]['min_bookings'] ?? 0);
                    $isUpgrade       = $newLevel->minBookings() > $oldMinBookings;

                    $this->line(
                        "Talent #{$talent->id} ({$talent->stage_name}): "
                        . "{$oldLevelValue} → {$newLevel->value}"
                        . ($isUpgrade ? ' ↑' : ' ↓')
                        . ($isDryRun ? ' [DRY-RUN]' : ''),
                    );

                    if (! $isDryRun) {
                        $talent->update(['talent_level' => $newLevel]);

                        if ($isUpgrade) {
                            SendPushNotification::dispatch(
                                userId: $talent->user_id,
                                title: '🎉 Niveau ' . $newLevel->label() . ' atteint !',
                                body: "Félicitations ! Votre visibilité sur BookMi vient d'augmenter.",
                                data: ['type' => 'talent_level_up', 'new_level' => $newLevel->value],
                            );
                        }
                    }

                    $updated++;
                }
            }
        });

        $this->info("Done. {$updated} talent(s) " . ($isDryRun ? 'would be ' : '') . 'updated.');

        return self::SUCCESS;
    }
}
