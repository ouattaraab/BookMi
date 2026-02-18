<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case Initiated  = 'initiated';
    case Processing = 'processing';
    case Succeeded  = 'succeeded';
    case Failed     = 'failed';
    case Refunded   = 'refunded';
}
