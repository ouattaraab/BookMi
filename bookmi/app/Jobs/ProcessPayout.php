<?php

namespace App\Jobs;

use App\Models\EscrowHold;
use App\Services\PayoutService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessPayout implements ShouldQueue
{
    use Queueable;

    /** @var int Maximum number of attempts (5 retries per NFR35). */
    public int $tries = 5;

    /**
     * Exponential backoff × 3: 10s, 30s, 90s, 270s, 810s.
     *
     * @var array<int>
     */
    public array $backoff = [10, 30, 90, 270, 810];

    public function __construct(
        public readonly int $escrowHoldId,
    ) {
        $this->onQueue('payouts');
    }

    public function handle(PayoutService $payoutService): void
    {
        $hold = EscrowHold::find($this->escrowHoldId);

        if (! $hold) {
            Log::warning("ProcessPayout: EscrowHold #{$this->escrowHoldId} not found — skipping.");

            return;
        }

        $payoutService->processPayout($hold);
    }
}
