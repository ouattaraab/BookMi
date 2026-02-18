<?php

namespace App\Enums;

enum EscrowStatus: string
{
    case Held     = 'held';
    case Released = 'released';
    case Refunded = 'refunded';
    case Disputed = 'disputed';
}
