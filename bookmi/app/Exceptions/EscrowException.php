<?php

namespace App\Exceptions;

class EscrowException extends BookmiException
{
    public static function escrowNotHeld(string $status): self
    {
        return new self(
            'ESCROW_NOT_HELD',
            "Le séquestre ne peut pas être libéré (statut: {$status}).",
        );
    }

    public static function bookingNotConfirmable(string $status): self
    {
        return new self(
            'ESCROW_BOOKING_NOT_CONFIRMABLE',
            "La réservation ne peut pas être confirmée pour libération du séquestre (statut: {$status}).",
        );
    }

    public static function forbidden(): self
    {
        return new self(
            'ESCROW_FORBIDDEN',
            "Vous n'êtes pas autorisé à effectuer cette action sur le séquestre.",
            403,
        );
    }
}
