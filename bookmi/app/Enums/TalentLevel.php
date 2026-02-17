<?php

namespace App\Enums;

enum TalentLevel: string
{
    case NOUVEAU = 'nouveau';
    case CONFIRME = 'confirme';
    case POPULAIRE = 'populaire';
    case ELITE = 'elite';

    public function label(): string
    {
        return match ($this) {
            self::NOUVEAU => 'Nouveau',
            self::CONFIRME => 'Confirmé',
            self::POPULAIRE => 'Populaire',
            self::ELITE => 'Élite',
        };
    }

    public function minBookings(): int
    {
        return match ($this) {
            self::NOUVEAU => 0,
            self::CONFIRME => (int) config('bookmi.talent.levels.confirme.min_bookings', 5),
            self::POPULAIRE => (int) config('bookmi.talent.levels.premium.min_bookings', 20),
            self::ELITE => (int) config('bookmi.talent.levels.elite.min_bookings', 50),
        };
    }
}
