<?php

namespace App\Events;

use App\Models\EscrowHold;
use App\Models\Transaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentReceived
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Transaction $transaction,
        public readonly EscrowHold $escrowHold,
    ) {}
}
