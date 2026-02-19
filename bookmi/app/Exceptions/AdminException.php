<?php

namespace App\Exceptions;

class AdminException extends BookmiException
{
    public static function userNotFound(): self
    {
        return new self('USER_NOT_FOUND', 'Utilisateur introuvable.', 404);
    }

    public static function alreadySuspended(): self
    {
        return new self('ALREADY_SUSPENDED', 'Ce compte est déjà suspendu.', 422);
    }

    public static function notSuspended(): self
    {
        return new self('NOT_SUSPENDED', 'Ce compte n\'est pas suspendu.', 422);
    }

    public static function cannotSuspendAdmin(): self
    {
        return new self('CANNOT_SUSPEND_ADMIN', 'Impossible de suspendre un compte administrateur.', 403);
    }

    public static function alertNotFound(): self
    {
        return new self('ALERT_NOT_FOUND', 'Alerte introuvable.', 404);
    }

    public static function alertAlreadyClosed(): self
    {
        return new self('ALERT_ALREADY_CLOSED', 'Cette alerte est déjà fermée.', 422);
    }

    public static function reviewNotFound(): self
    {
        return new self('REVIEW_NOT_FOUND', 'Avis introuvable.', 404);
    }

    public static function reviewNotReported(): self
    {
        return new self('REVIEW_NOT_REPORTED', 'Cet avis n\'a pas été signalé.', 422);
    }

    public static function insufficientAdminRole(): self
    {
        return new self('INSUFFICIENT_ADMIN_ROLE', 'Vous n\'avez pas le rôle requis pour cette action.', 403);
    }

    public static function cannotModifyOwnRole(): self
    {
        return new self('CANNOT_MODIFY_OWN_ROLE', 'Vous ne pouvez pas modifier votre propre rôle.', 403);
    }
}
