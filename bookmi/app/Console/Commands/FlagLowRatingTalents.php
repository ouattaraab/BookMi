<?php

namespace App\Console\Commands;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use App\Models\TalentProfile;
use App\Services\AlertService;
use Illuminate\Console\Command;

class FlagLowRatingTalents extends Command
{
    protected $signature   = 'bookmi:flag-low-rating-talents {--dry-run : Log without creating alerts}';
    protected $description = 'Flag talents with average rating below the configured threshold (Story 8.4)';

    public function __construct(private readonly AlertService $alerts)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $threshold = (float) config('bookmi.talent.low_rating_threshold', 3.0);
        $isDryRun  = $this->option('dry-run');

        $this->info("Checking talents with average_rating < {$threshold}…");

        TalentProfile::where('average_rating', '>', 0)
            ->where('average_rating', '<', $threshold)
            ->where('total_bookings', '>', 0)
            ->chunk(100, function ($talents) use ($threshold, $isDryRun) {
                foreach ($talents as $talent) {
                    // Skip if open alert already exists
                    if ($this->alerts->openExists(AlertType::LowRating, $talent)) {
                        $this->line("Skipped #{$talent->id} ({$talent->stage_name}) — alert already open.");
                        continue;
                    }

                    $this->warn("Low rating: #{$talent->id} {$talent->stage_name} — {$talent->average_rating}");

                    if (! $isDryRun) {
                        $this->alerts->create(
                            type: AlertType::LowRating,
                            severity: AlertSeverity::Warning,
                            title: "Note basse — {$talent->stage_name}",
                            description: "Le talent {$talent->stage_name} a une note moyenne de {$talent->average_rating} (seuil: {$threshold}).",
                            subject: $talent,
                            metadata: [
                                'average_rating'  => $talent->average_rating,
                                'total_bookings'  => $talent->total_bookings,
                                'threshold'       => $threshold,
                            ],
                        );
                    }
                }
            });

        $this->info('Done.');

        return self::SUCCESS;
    }
}
