<?php

namespace App\Enums;

enum AlertType: string
{
    case LowRating         = 'low_rating';
    case SuspiciousActivity = 'suspicious_activity';
    case PendingAction     = 'pending_action';

    public function label(): string
    {
        return match ($this) {
            self::LowRating         => 'Note basse',
            self::SuspiciousActivity => 'Activité suspecte',
            self::PendingAction     => 'Action en attente',
        };
    }
}
