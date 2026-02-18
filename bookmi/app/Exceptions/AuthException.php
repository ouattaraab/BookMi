<?php

namespace App\Exceptions;

class AuthException extends BookmiException
{
    public static function otpExpired(): self
    {
        return new self(
            'AUTH_OTP_EXPIRED',
            'Le code OTP a expiré. Demandez un nouveau code.',
        );
    }

    public static function otpInvalid(int $remainingAttempts): self
    {
        return new self(
            'AUTH_OTP_INVALID',
            'Le code OTP est invalide.',
            422,
            ['remaining_attempts' => $remainingAttempts],
        );
    }

    public static function accountLocked(string $lockedUntil, int $remainingSeconds): self
    {
        return new self(
            'AUTH_ACCOUNT_LOCKED',
            'Compte temporairement bloqué après trop de tentatives échouées.',
            422,
            ['locked_until' => $lockedUntil, 'remaining_seconds' => $remainingSeconds],
        );
    }

    public static function otpResendLimit(): self
    {
        return new self(
            'AUTH_OTP_RESEND_LIMIT',
            'Limite de renvoi OTP atteinte. Réessayez dans une heure.',
            429,
        );
    }

    public static function invalidCredentials(int $remainingAttempts): self
    {
        return new self(
            'AUTH_INVALID_CREDENTIALS',
            'Identifiants invalides.',
            422,
            ['remaining_attempts' => $remainingAttempts],
        );
    }

    public static function phoneNotVerified(): self
    {
        return new self(
            'AUTH_PHONE_NOT_VERIFIED',
            'Veuillez vérifier votre numéro de téléphone avant de vous connecter.',
        );
    }

    public static function accountDisabled(): self
    {
        return new self(
            'AUTH_ACCOUNT_DISABLED',
            'Ce compte a été désactivé.',
        );
    }

    public static function resetTokenInvalid(): self
    {
        return new self(
            'AUTH_RESET_TOKEN_INVALID',
            'Le lien de réinitialisation est invalide ou a expiré.',
        );
    }

    public static function resetThrottled(): self
    {
        return new self(
            'AUTH_RESET_THROTTLED',
            'Veuillez patienter avant de demander un nouveau lien.',
            429,
        );
    }
}
