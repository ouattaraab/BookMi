<?php

namespace App\Exceptions;

class PaymentException extends BookmiException
{
    public static function bookingNotPayable(string $status): self
    {
        return new self(
            'PAYMENT_BOOKING_NOT_PAYABLE',
            "La réservation ne peut pas être payée (statut: {$status}).",
        );
    }

    public static function gatewayError(string $gateway, string $message): self
    {
        return new self(
            'PAYMENT_GATEWAY_ERROR',
            "Erreur de passerelle de paiement ({$gateway}): {$message}.",
            502,
        );
    }

    public static function unsupportedMethod(string $method): self
    {
        return new self(
            'PAYMENT_UNSUPPORTED_METHOD',
            "Méthode de paiement non supportée: {$method}.",
        );
    }

    public static function duplicateTransaction(): self
    {
        return new self(
            'PAYMENT_DUPLICATE',
            'Une transaction est déjà en cours pour cette réservation.',
            409,
        );
    }
}
