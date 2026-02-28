<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\BookingRequest;
use App\Models\TalentProfile;
use Illuminate\Console\Command;

class UpdateVisibilityScores extends Command
{
    protected $signature = 'bookmi:update-visibility-scores {--dry-run : Log only, do not save}';

    protected $description = 'Recalculate visibility scores for all talent profiles.';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $updated = 0;

        TalentProfile::with('user')->chunk(100, function ($talents) use ($isDryRun, &$updated) {
            foreach ($talents as $talent) {
                $recentCompleted = BookingRequest::where('talent_profile_id', $talent->id)
                    ->where('status', BookingStatus::Completed->value)
                    ->where('updated_at', '>=', now()->subDays(30))
                    ->count();

                $activityScore = min($recentCompleted, 5) / 5 * 40;
                $ratingScore   = ($talent->average_rating / 5) * 40;
                $verifiedScore = $talent->is_verified ? 20 : 0;

                $score = min(100, $activityScore + $ratingScore + $verifiedScore);

                $this->line(
                    "Talent #{$talent->id} ({$talent->stage_name}): score={$score}"
                    . ($isDryRun ? ' [DRY-RUN]' : ''),
                );

                if (! $isDryRun) {
                    $talent->update(['visibility_score' => $score]);
                }

                $updated++;
            }
        });

        $this->info("{$updated} talent(s) " . ($isDryRun ? 'would be ' : '') . 'updated.');

        return self::SUCCESS;
    }
}
