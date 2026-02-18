<?php

namespace App\Exceptions;

class BookingException extends BookmiException
{
    public static function dateUnavailable(): self
    {
        return new self(
            'BOOKING_DATE_UNAVAILABLE',
            'La date demandée n\'est pas disponible pour ce talent.',
            422,
        );
    }

    public static function talentNotFound(): self
    {
        return new self(
            'BOOKING_TALENT_NOT_FOUND',
            'Le profil talent est introuvable.',
            404,
        );
    }

    public static function packageNotBelongToTalent(): self
    {
        return new self(
            'BOOKING_PACKAGE_MISMATCH',
            'Ce package n\'appartient pas au talent spécifié.',
            422,
        );
    }

    public static function invalidStatusTransition(): self
    {
        return new self(
            'BOOKING_INVALID_TRANSITION',
            'Cette transition de statut n\'est pas autorisée.',
            422,
        );
    }

    public static function contractNotReady(): self
    {
        return new self(
            'BOOKING_CONTRACT_NOT_READY',
            'Le contrat n\'est pas encore disponible. Veuillez réessayer dans quelques instants.',
            404,
        );
    }
}
