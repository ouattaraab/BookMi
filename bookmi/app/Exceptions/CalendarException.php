<?php

namespace App\Exceptions;

class CalendarException extends BookmiException
{
    public static function slotConflict(): self
    {
        return new self(
            'CALENDAR_SLOT_CONFLICT',
            'Un créneau existe déjà pour cette date.',
            409,
        );
    }

    public static function slotNotFound(): self
    {
        return new self(
            'CALENDAR_SLOT_NOT_FOUND',
            'Le créneau calendrier est introuvable.',
            404,
        );
    }

    public static function invalidMonth(): self
    {
        return new self(
            'CALENDAR_INVALID_MONTH',
            'Le paramètre month doit être au format AAAA-MM (ex: 2026-03).',
            422,
        );
    }
}
