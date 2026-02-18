<?php

namespace App\Enums;

enum RescheduleStatus: string
{
    case Pending  = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
}
