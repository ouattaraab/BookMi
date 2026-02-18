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

    public static function rescheduleAlreadyPending(): self
    {
        return new self(
            'BOOKING_RESCHEDULE_ALREADY_PENDING',
            'Une demande de report est déjà en attente pour cette réservation.',
            422,
        );
    }

    public static function rescheduleNotPending(): self
    {
        return new self(
            'BOOKING_RESCHEDULE_NOT_PENDING',
            'Cette demande de report n\'est plus en attente.',
            422,
        );
    }

    public static function rescheduleSameDate(): self
    {
        return new self(
            'BOOKING_RESCHEDULE_SAME_DATE',
            'La date proposée est identique à la date actuelle de l\'événement.',
            422,
        );
    }

    public static function cancellationNotAllowed(): self
    {
        return new self(
            'BOOKING_CANCELLATION_NOT_ALLOWED',
            'L\'annulation n\'est plus possible à moins de 2 jours de l\'événement.',
            422,
        );
    }

    public static function cancellationRequiresMediation(): self
    {
        return new self(
            'BOOKING_CANCELLATION_MEDIATION_REQUIRED',
            'L\'annulation à moins de 7 jours de l\'événement nécessite une médiation.',
            422,
        );
    }

    public static function expressBookingNotAvailable(): self
    {
        return new self(
            'BOOKING_EXPRESS_NOT_AVAILABLE',
            'Ce talent n\'a pas activé la réservation express.',
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
