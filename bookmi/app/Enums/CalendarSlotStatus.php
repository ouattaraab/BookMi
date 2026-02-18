<?php

namespace App\Enums;

enum CalendarSlotStatus: string
{
    case Available = 'available';
    case Blocked   = 'blocked';
    case Rest      = 'rest';
    case Confirmed = 'confirmed'; // virtual — set by CalendarService when a confirmed booking exists
}
