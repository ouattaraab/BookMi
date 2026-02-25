<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Pending   = 'pending';
    case Accepted  = 'accepted';   // Talent a validé la réservation (Validée)
    case Paid      = 'paid';       // Client a payé (Confirmée)
    case Confirmed = 'confirmed';  // Paiement confirmé (Confirmée)
    case Completed = 'completed';  // Prestation terminée (Terminée)
    case Cancelled = 'cancelled';  // Client a annulé (Annulée)
    case Rejected  = 'rejected';   // Talent a rejeté (Rejetée)
    case Disputed  = 'disputed';

    /**
     * Returns valid target statuses from a given status.
     *
     * @return array<BookingStatus>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending   => [self::Accepted, self::Rejected, self::Cancelled],
            self::Accepted  => [self::Paid, self::Cancelled],
            self::Paid      => [self::Confirmed, self::Cancelled, self::Disputed],
            self::Confirmed => [self::Completed, self::Cancelled, self::Disputed],
            self::Completed => [],
            self::Cancelled => [],
            self::Rejected  => [],
            self::Disputed  => [self::Confirmed, self::Cancelled],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), strict: true);
    }
}
