<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Pending   = 'pending';
    case Accepted  = 'accepted';
    case Paid      = 'paid';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Disputed  = 'disputed';

    /**
     * Returns valid target statuses from a given status.
     *
     * @return array<BookingStatus>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending   => [self::Accepted, self::Cancelled],
            self::Accepted  => [self::Paid, self::Cancelled],
            self::Paid      => [self::Confirmed, self::Cancelled, self::Disputed],
            self::Confirmed => [self::Completed, self::Disputed],
            self::Completed => [],
            self::Cancelled => [],
            self::Disputed  => [self::Confirmed, self::Cancelled],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), strict: true);
    }
}
