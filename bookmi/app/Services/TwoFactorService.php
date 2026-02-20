<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    // -------------------------------------------------------------------------
    // TOTP
    // -------------------------------------------------------------------------

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * Generate QR code SVG for TOTP setup.
     */
    public function getQrCodeSvg(User $user, string $secret): string
    {
        $appName = config('app.name', 'BookMi');
        $otpAuthUrl = $this->google2fa->getQRCodeUrl(
            $appName,
            $user->email,
            $secret,
        );

        $renderer = new ImageRenderer(
            new RendererStyle(192),
            new SvgImageBackEnd(),
        );
        $writer = new Writer($renderer);

        return $writer->writeString($otpAuthUrl);
    }

    public function verifyTotp(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code, 1);
    }

    // -------------------------------------------------------------------------
    // Email OTP
    // -------------------------------------------------------------------------

    public function sendEmailOtp(User $user): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put("2fa_email_otp:{$user->id}", $code, now()->addMinutes(10));
        $user->notify(new TwoFactorCodeNotification($code));
    }

    public function verifyEmailOtp(User $user, string $code): bool
    {
        $stored = Cache::get("2fa_email_otp:{$user->id}");
        if ($stored === null || ! hash_equals($stored, $code)) {
            return false;
        }
        Cache::forget("2fa_email_otp:{$user->id}");
        return true;
    }

    // -------------------------------------------------------------------------
    // Challenge token (used after credentials pass but 2FA not yet verified)
    // -------------------------------------------------------------------------

    public function generateChallengeToken(User $user): string
    {
        $token = Str::random(64);
        Cache::put("2fa_challenge:{$token}", $user->id, now()->addMinutes(10));
        return $token;
    }

    public function validateChallengeToken(string $token): ?User
    {
        $userId = Cache::get("2fa_challenge:{$token}");
        if (! $userId) {
            return null;
        }
        Cache::forget("2fa_challenge:{$token}");
        return User::find($userId);
    }

    // -------------------------------------------------------------------------
    // Setup / Enable / Disable
    // -------------------------------------------------------------------------

    /**
     * Begin TOTP setup: generate a secret, store it temporarily, return QR code.
     *
     * @return array{secret: string, qr_code_svg: string}
     */
    public function setupTotp(User $user): array
    {
        $secret = $this->generateSecret();
        // Store the pending secret in cache (not in DB yet — user must confirm first)
        Cache::put("2fa_pending_secret:{$user->id}", $secret, now()->addMinutes(30));

        return [
            'secret'      => $secret,
            'qr_code_svg' => $this->getQrCodeSvg($user, $secret),
        ];
    }

    /**
     * Confirm TOTP setup with the user's first code → persist to DB.
     *
     * @throws ValidationException
     */
    public function enableTotp(User $user, string $code): void
    {
        $secret = Cache::get("2fa_pending_secret:{$user->id}");

        if (! $secret || ! $this->verifyTotp($secret, $code)) {
            throw ValidationException::withMessages([
                'code' => 'Code invalide. Vérifiez votre application d\'authentification.',
            ]);
        }

        Cache::forget("2fa_pending_secret:{$user->id}");

        $user->update([
            'two_factor_enabled'      => true,
            'two_factor_method'       => 'totp',
            'two_factor_secret'       => $secret,
            'two_factor_confirmed_at' => now(),
        ]);
    }

    /**
     * Send email OTP for setup and wait for confirmation.
     */
    public function setupEmail(User $user): void
    {
        $this->sendEmailOtp($user);
    }

    /**
     * Confirm email OTP → enable email 2FA in DB.
     *
     * @throws ValidationException
     */
    public function enableEmail(User $user, string $code): void
    {
        if (! $this->verifyEmailOtp($user, $code)) {
            throw ValidationException::withMessages([
                'code' => 'Code invalide ou expiré.',
            ]);
        }

        $user->update([
            'two_factor_enabled'      => true,
            'two_factor_method'       => 'email',
            'two_factor_secret'       => null,
            'two_factor_confirmed_at' => now(),
        ]);
    }

    /**
     * Disable 2FA entirely.
     */
    public function disable(User $user): void
    {
        $user->update([
            'two_factor_enabled'      => false,
            'two_factor_method'       => null,
            'two_factor_secret'       => null,
            'two_factor_confirmed_at' => null,
        ]);
    }
}
