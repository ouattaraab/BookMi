<?php

namespace App\Console\Commands;

use App\Enums\TalentLevel;
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
                    $this->line(
                        "Talent #{$talent->id} ({$talent->stage_name}): "
                        . "{$talent->talent_level?->value} → {$newLevel->value}"
                        . ($isDryRun ? ' [DRY-RUN]' : ''),
                    );

                    if (! $isDryRun) {
                        $talent->update(['talent_level' => $newLevel]);
                    }

                    $updated++;
                }
            }
        });

        $this->info("Done. {$updated} talent(s) " . ($isDryRun ? 'would be ' : '') . 'updated.');

        return self::SUCCESS;
    }
}
