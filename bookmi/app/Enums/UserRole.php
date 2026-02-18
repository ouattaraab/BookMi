<?php

namespace App\Enums;

enum UserRole: string
{
    case CLIENT = 'client';
    case TALENT = 'talent';
    case MANAGER = 'manager';
    case ADMIN_CEO = 'admin_ceo';
    case ADMIN_COMPTABLE = 'admin_comptable';
    case ADMIN_CONTROLEUR = 'admin_controleur';
    case ADMIN_MODERATEUR = 'admin_moderateur';

    public function label(): string
    {
        return match ($this) {
            self::CLIENT => 'Client',
            self::TALENT => 'Talent',
            self::MANAGER => 'Manager',
            self::ADMIN_CEO => 'Administrateur CEO',
            self::ADMIN_COMPTABLE => 'Administrateur Comptable',
            self::ADMIN_CONTROLEUR => 'Administrateur Contrôleur',
            self::ADMIN_MODERATEUR => 'Administrateur Modérateur',
        };
    }

    /**
     * @return array<string>
     */
    public static function registrableRoles(): array
    {
        return [self::CLIENT->value, self::TALENT->value];
    }
}
