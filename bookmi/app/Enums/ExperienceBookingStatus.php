<?php

namespace App\Enums;

enum ExperienceBookingStatus: string
{
    case Pending   = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Pending   => 'En attente',
            self::Confirmed => 'Confirmé',
            self::Cancelled => 'Annulé',
        };
    }

    public function filamentColor(): string
    {
        return match($this) {
            self::Pending   => 'warning',
            self::Confirmed => 'success',
            self::Cancelled => 'danger',
        };
    }
}
