<?php

namespace App\Listeners;

use App\Events\EscrowReleased;
use App\Jobs\ProcessPayout;

class HandleEscrowReleased
{
    /**
     * Dispatch the ProcessPayout job with a configurable delay (default 24h per NFR).
     * The delay allows BookMi to review the payout before it hits the talent's account.
     */
    public function handle(EscrowReleased $event): void
    {
        $delayHours = config('bookmi.escrow.payout_delay_hours', 24);

        ProcessPayout::dispatch($event->escrowHold->id)
            ->delay(now()->addHours($delayHours));
    }
}
