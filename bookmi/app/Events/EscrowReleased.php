<?php

namespace App\Events;

use App\Models\EscrowHold;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EscrowReleased
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly EscrowHold $escrowHold,
    ) {
    }
}
