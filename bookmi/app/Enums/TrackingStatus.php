<?php

namespace App\Enums;

enum TrackingStatus: string
{
    case Preparing  = 'preparing';
    case EnRoute    = 'en_route';
    case Arrived    = 'arrived';
    case Performing = 'performing';
    case Completed  = 'completed';

    /**
     * @return array<TrackingStatus>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Preparing  => [self::EnRoute],
            self::EnRoute    => [self::Arrived],
            self::Arrived    => [self::Performing],
            self::Performing => [self::Completed],
            self::Completed  => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), strict: true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Preparing  => 'En préparation',
            self::EnRoute    => 'En route',
            self::Arrived    => 'Arrivé sur place',
            self::Performing => 'En prestation',
            self::Completed  => 'Prestation terminée',
        };
    }
}
