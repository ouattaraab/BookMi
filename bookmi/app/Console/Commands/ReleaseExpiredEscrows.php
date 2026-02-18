<?php

namespace App\Console\Commands;

use App\Enums\EscrowStatus;
use App\Models\EscrowHold;
use App\Services\EscrowService;
use Illuminate\Console\Command;

class ReleaseExpiredEscrows extends Command
{
    protected $signature = 'escrow:release-expired';

    protected $description = 'Release escrow holds whose release_scheduled_at has passed (auto-confirm after 48h).';

    public function __construct(
        private readonly EscrowService $escrowService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $total    = 0;
        $released = 0;
        $failed   = 0;

        EscrowHold::where('status', EscrowStatus::Held->value)
            ->where('release_scheduled_at', '<=', now())
            ->chunkById(100, function ($holds) use (&$total, &$released, &$failed) {
                foreach ($holds as $hold) {
                    $total++;
                    try {
                        $this->escrowService->releaseEscrow($hold);
                        $released++;
                    } catch (\Throwable $e) {
                        $this->error("Failed to release escrow #{$hold->id}: {$e->getMessage()}");
                        $failed++;
                    }
                }
            });

        if ($total === 0) {
            $this->info('No expired escrow holds to release.');

            return self::SUCCESS;
        }

        $this->info("Released {$released} escrow(s). Failed: {$failed}. Total expired: {$total}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
