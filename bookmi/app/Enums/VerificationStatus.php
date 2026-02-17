<?php

namespace App\Enums;

enum VerificationStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::APPROVED => 'Approuvé',
            self::REJECTED => 'Rejeté',
        };
    }

    public function isTerminal(): bool
    {
        return match ($this) {
            self::PENDING => false,
            self::APPROVED, self::REJECTED => true,
        };
    }
}
