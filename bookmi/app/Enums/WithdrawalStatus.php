<?php

namespace App\Enums;

enum WithdrawalStatus: string
{
    case Pending    = 'pending';
    case Approved   = 'approved';
    case Processing = 'processing';
    case Completed  = 'completed';
    case Rejected   = 'rejected';

    public function label(): string
    {
        return match($this) {
            self::Pending    => 'En attente',
            self::Approved   => 'Approuvée',
            self::Processing => 'En cours de traitement',
            self::Completed  => 'Complétée',
            self::Rejected   => 'Rejetée',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pending    => 'warning',
            self::Approved   => 'info',
            self::Processing => 'primary',
            self::Completed  => 'success',
            self::Rejected   => 'danger',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Rejected]);
    }
}
