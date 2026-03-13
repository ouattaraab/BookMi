<?php

namespace App\Enums;

enum ExperienceStatus: string
{
    case Draft     = 'draft';
    case Published = 'published';
    case Full      = 'full';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function label(): string
    {
        return match($this) {
            self::Draft     => 'Brouillon',
            self::Published => 'Publié',
            self::Full      => 'Complet',
            self::Cancelled => 'Annulé',
            self::Completed => 'Terminé',
        };
    }

    public function filamentColor(): string
    {
        return match($this) {
            self::Draft     => 'gray',
            self::Published => 'success',
            self::Full      => 'warning',
            self::Cancelled => 'danger',
            self::Completed => 'primary',
        };
    }

    public function cssClass(): string
    {
        return match($this) {
            self::Draft     => 'exp-badge-draft',
            self::Published => 'exp-badge-published',
            self::Full      => 'exp-badge-full',
            self::Cancelled => 'exp-badge-cancelled',
            self::Completed => 'exp-badge-completed',
        };
    }

    /** Returns statuses that are publicly visible on the landing / profile. */
    public static function visibleOnPublic(): array
    {
        return [self::Published->value, self::Full->value];
    }
}
