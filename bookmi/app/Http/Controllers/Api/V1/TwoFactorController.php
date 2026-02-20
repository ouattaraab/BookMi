<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\AuthService;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TwoFactorController extends BaseController
{
    public function __construct(
        private readonly TwoFactorService $twoFactorService,
        private readonly AuthService $authService,
    ) {
    }

    /**
     * GET /auth/2fa/status
     * Returns current 2FA configuration for the authenticated user.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->successResponse([
            'enabled' => (bool) $user->two_factor_enabled,
            'method'  => $user->two_factor_method,
        ]);
    }

    /**
     * POST /auth/2fa/setup/totp
     * Generate TOTP secret + QR code for setup (not enabled yet).
     */
    public function setupTotp(Request $request): JsonResponse
    {
        $data = $this->twoFactorService->setupTotp($request->user());

        return $this->successResponse($data);
    }

    /**
     * POST /auth/2fa/enable/totp
     * Confirm TOTP code → enable TOTP 2FA.
     */
    public function enableTotp(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ]);

        $this->twoFactorService->enableTotp($request->user(), $request->input('code'));

        return $this->successResponse([
            'message' => 'Authentification à deux facteurs (TOTP) activée avec succès.',
        ]);
    }

    /**
     * POST /auth/2fa/setup/email
     * Send a 6-digit code to the user's email for setup.
     */
    public function setupEmail(Request $request): JsonResponse
    {
        $this->twoFactorService->setupEmail($request->user());

        return $this->successResponse([
            'message' => 'Un code de vérification a été envoyé à votre adresse email.',
        ]);
    }

    /**
     * POST /auth/2fa/enable/email
     * Confirm email OTP → enable email 2FA.
     */
    public function enableEmail(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ]);

        $this->twoFactorService->enableEmail($request->user(), $request->input('code'));

        return $this->successResponse([
            'message' => 'Authentification à deux facteurs (Email) activée avec succès.',
        ]);
    }

    /**
     * POST /auth/2fa/verify
     * Public endpoint: exchange challenge_token + 2FA code for a full auth token.
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'challenge_token' => ['required', 'string'],
            'code'            => ['required', 'string', 'digits:6'],
        ]);

        $user = $this->twoFactorService->validateChallengeToken($request->input('challenge_token'));

        if (! $user) {
            throw ValidationException::withMessages([
                'challenge_token' => 'Token de challenge invalide ou expiré. Veuillez vous reconnecter.',
            ]);
        }

        $code   = $request->input('code');
        $valid  = false;

        if ($user->two_factor_method === 'totp') {
            $valid = $this->twoFactorService->verifyTotp($user->two_factor_secret, $code);
        } elseif ($user->two_factor_method === 'email') {
            $valid = $this->twoFactorService->verifyEmailOtp($user, $code);
        }

        if (! $valid) {
            throw ValidationException::withMessages([
                'code' => 'Code invalide ou expiré.',
            ]);
        }

        // Build full auth response (token + user data)
        $result = $this->authService->buildAuthResponsePublic($user);

        return $this->successResponse($result);
    }

    /**
     * POST /auth/2fa/disable
     * Disable 2FA after verifying the user's password.
     */
    public function disable(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Mot de passe incorrect.',
            ]);
        }

        $this->twoFactorService->disable($user);

        return $this->successResponse([
            'message' => 'Authentification à deux facteurs désactivée.',
        ]);
    }
}
