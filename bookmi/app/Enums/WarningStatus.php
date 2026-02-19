<?php

namespace App\Enums;

enum WarningStatus: string
{
    case Active   = 'active';
    case Resolved = 'resolved';
}
