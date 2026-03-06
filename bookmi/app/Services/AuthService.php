<?php

namespace App\Services;

use App\Events\PasswordReset;
use App\Events\UserLoggedIn;
use App\Events\UserLoggedOut;
use App\Exceptions\AuthException;
use App\Models\LoginLockoutLog;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthService
{
    public function __construct(
        private readonly SmsService $smsService,
        private readonly TwoFactorService $twoFactorService,
        private readonly ReferralService $referralService,
        private readonly ConsentService $consentService,
    ) {
    }

    /**
     * Register a new user (client or talent).
     *
     * Note: category_id/subcategory_id are validated in RegisterRequest
     * but not stored here. The talent will associate their category when
     * creating their TalentProfile (POST /talent_profiles). The validation
     * at registration ensures the talent selects a valid category upfront.
     */
    public function register(array $data, ?Request $request = null): User
    {
        return DB::transaction(function () use ($data, $request) {
            $user = User::create([
                'first_name'        => $data['first_name'],
                'last_name'         => $data['last_name'],
                'email'             => $data['email'],
                'phone'             => $data['phone'],
                'password'          => $data['password'],
                'is_active'         => true,
                'phone_verified_at' => now(), // Vérification OTP désactivée — marqué comme vérifié d'emblée
            ]);

            $user->assignRole($data['role']);

            // Record consents provided at registration
            if (! empty($data['consents']) && is_array($data['consents'])) {
                $this->consentService->recordConsents($user, $data['consents'], $request);
            }

            // Auto-generate a referral code for every new user
            $this->referralService->ensureCodeFor($user);

            // Apply referral code if provided at registration
            if (! empty($data['referral_code'])) {
                $this->referralService->applyCode($user, $data['referral_code']);
            }

            $user->notify(new WelcomeNotification($data['role']));

            return $user;
        });
    }

    /**
     * Verify OTP code for phone number.
     *
     * @return array{token: string, user: array<string, mixed>, roles: array<int, string>}
     */
    public function verifyOtp(string $phone, string $code): array
    {
        $lockoutKey = "otp_lockout:{$phone}";
        $attemptsKey = "otp_attempts:{$phone}";
        $otpKey = "otp:{$phone}";
        $resendKey = "otp_resend_count:{$phone}";

        // 1. Check lockout
        $lockedUntilIso = Cache::get($lockoutKey);
        if ($lockedUntilIso !== null) {
            $lockedUntil = \Carbon\Carbon::parse($lockedUntilIso);
            $remainingSeconds = (int) max(0, now()->diffInSeconds($lockedUntil, false));

            throw AuthException::accountLocked($lockedUntilIso, $remainingSeconds);
        }

        // 2. Check OTP exists (not expired)
        $storedCode = Cache::get($otpKey);
        if ($storedCode === null) {
            throw AuthException::otpExpired();
        }

        // 3. Compare codes (constant-time to prevent timing attacks)
        if (! hash_equals($storedCode, $code)) {
            $attempts = Cache::increment($attemptsKey);

            // Set TTL on first attempt
            if ($attempts === 1) {
                $lockoutMinutes = (int) config('bookmi.auth.lockout_minutes', 15);
                Cache::put($attemptsKey, $attempts, now()->addMinutes($lockoutMinutes));
            }

            $maxAttempts = (int) config('bookmi.auth.max_login_attempts', 5);

            if ($attempts >= $maxAttempts) {
                $lockoutMinutes = (int) config('bookmi.auth.lockout_minutes', 15);
                $lockedUntil = now()->addMinutes($lockoutMinutes);
                Cache::put($lockoutKey, $lockedUntil->toIso8601String(), $lockedUntil);
                Cache::forget($attemptsKey);
                Cache::forget($otpKey);

                throw AuthException::accountLocked($lockedUntil->toIso8601String(), $lockoutMinutes * 60);
            }

            throw AuthException::otpInvalid($maxAttempts - $attempts);
        }

        // 4. Code is valid — cleanup and return token
        Cache::forget($otpKey);
        Cache::forget($attemptsKey);
        Cache::forget($resendKey);

        $user = User::where('phone', $phone)->firstOrFail();
        $verifiedAt = now();
        $user->phone_verified_at = $verifiedAt;
        $user->save();

        return $this->buildAuthResponse($user, $verifiedAt);
    }

    /**
     * Resend OTP code to the given phone number.
     */
    public function resendOtp(string $phone): void
    {
        // [H3] Silently no-op for unknown numbers — same response as success to prevent enumeration.
        if (! User::where('phone', $phone)->exists()) {
            return;
        }

        $resendKey = "otp_resend_count:{$phone}";
        $maxResend = (int) config('bookmi.auth.otp_max_resend_per_hour', 3);

        $currentCount = (int) Cache::get($resendKey, 0);

        if ($currentCount >= $maxResend) {
            throw AuthException::otpResendLimit();
        }

        if ($currentCount === 0) {
            Cache::put($resendKey, 1, now()->addHour());
        } else {
            Cache::increment($resendKey);
        }

        $this->sendOtp($phone);
    }

    /**
     * Authenticate user with email and password.
     *
     * Returns either a full auth response OR a 2FA challenge if 2FA is enabled.
     *
     * @param  array{client_type?: string, ip?: string|null, user_agent?: string|null}|null  $context
     * @return array{token: string, user: array<string, mixed>, roles: array<int, string>}
     *       | array{two_factor_required: true, challenge_token: string, method: string}
     */
    public function login(string $email, string $password, ?array $context = null): array
    {
        $email = strtolower($email);
        $lockoutKey = "login_lockout:{$email}";

        // 1. Check lockout (atomic — no Cache::has)
        $lockedUntilIso = Cache::get($lockoutKey);
        if ($lockedUntilIso !== null) {
            $lockedUntil = Carbon::parse($lockedUntilIso);
            $remainingSeconds = (int) max(0, now()->diffInSeconds($lockedUntil, false));

            throw AuthException::accountLocked($lockedUntilIso, $remainingSeconds);
        }

        // 2. Find user by email (same error for not found and wrong password — prevents enumeration)
        $user = User::where('email', $email)->first();
        if (! $user) {
            $this->handleFailedLoginAttempt($email, $context);
        }

        // 3. Verify password
        if (! Hash::check($password, $user->password)) {
            $this->handleFailedLoginAttempt($email, $context);
        }

        // 4. Check phone verified
        if ($user->phone_verified_at === null) {
            throw AuthException::phoneNotVerified();
        }

        // 5. Check account active
        if (! $user->is_active) {
            throw AuthException::accountDisabled();
        }

        // 6. Clear counters
        Cache::forget("login_attempts:{$email}");
        Cache::forget($lockoutKey);

        // 7. 2FA check — if enabled, issue challenge token instead of a session token
        if ($user->two_factor_enabled) {
            $challengeToken = $this->twoFactorService->generateChallengeToken($user);

            if ($user->two_factor_method === 'email') {
                $this->twoFactorService->sendEmailOtp($user);
            }

            return [
                'two_factor_required' => true,
                'challenge_token'     => $challengeToken,
                'method'              => $user->two_factor_method,
            ];
        }

        // 8. No 2FA — success, create token, dispatch event
        UserLoggedIn::dispatch($user);

        return $this->buildAuthResponse($user);
    }

    /**
     * Build standardized auth response with token, user data, and roles.
     * Alias for external callers (e.g. TwoFactorController after challenge validation).
     *
     * @return array{token: string, user: array<string, mixed>, roles: array<int, string>}
     */
    public function buildAuthResponsePublic(User $user): array
    {
        UserLoggedIn::dispatch($user);
        return $this->buildAuthResponse($user);
    }

    /**
     * Build standardized auth response with token, user data, and roles.
     *
     * @return array{token: string, user: array<string, mixed>, roles: array<int, string>}
     */
    private function buildAuthResponse(User $user, ?\Carbon\Carbon $phoneVerifiedAtOverride = null): array
    {
        $expirationHours = (int) config('bookmi.auth.token_expiration_hours', 24);
        $token = $user->createToken('auth-token', expiresAt: now()->addHours($expirationHours))->plainTextToken;

        /** @var \Carbon\Carbon $phoneVerifiedAt */
        $phoneVerifiedAt = $phoneVerifiedAtOverride ?? $user->phone_verified_at;

        $user->loadMissing('talentProfile');
        $tp = $user->talentProfile;

        return [
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'phone_verified_at' => $phoneVerifiedAt->toIso8601String(),
                'is_active' => $user->is_active,
                'is_admin' => (bool) $user->is_admin,
                'is_client_verified' => (bool) $user->is_client_verified,
                'avatar_url' => $user->avatar_url,
                'talentProfile' => $tp ? [
                    'id' => $tp->id,
                    'stage_name' => $tp->stage_name,
                    'slug' => $tp->slug,
                    'talent_level' => $tp->talent_level instanceof \BackedEnum ? $tp->talent_level->value : $tp->talent_level,
                    'is_verified' => (bool) $tp->is_verified,
                ] : null,
            ],
            'roles' => $user->getRoleNames()->toArray(),
        ];
    }

    /**
     * Verify if the given email is currently locked out and throw if so.
     * Used by external callers (e.g. WebLoginController) to check the shared lockout.
     *
     * @throws AuthException if the account is locked
     */
    public function checkLockout(string $email): void
    {
        $lockoutKey = 'login_lockout:' . strtolower($email);
        $lockedUntilIso = Cache::get($lockoutKey);

        if ($lockedUntilIso !== null) {
            $lockedUntil = Carbon::parse($lockedUntilIso);
            $remainingSeconds = (int) max(0, now()->diffInSeconds($lockedUntil, false));
            throw AuthException::accountLocked($lockedUntilIso, $remainingSeconds);
        }
    }

    /**
     * Record a failed login attempt and trigger a lockout when the threshold is reached.
     * Does NOT throw — caller is responsible for its own response logic.
     *
     * @param  array{client_type?: string, ip?: string|null, user_agent?: string|null}  $context
     */
    public function trackFailedAttempt(string $email, array $context = []): void
    {
        $email = strtolower($email);
        $attemptsKey = "login_attempts:{$email}";
        $lockoutKey = "login_lockout:{$email}";
        $lockoutMinutes = (int) config('bookmi.auth.lockout_minutes', 15);
        $maxAttempts = (int) config('bookmi.auth.max_login_attempts', 5);

        $attempts = (int) Cache::increment($attemptsKey);

        if ($attempts === 1) {
            Cache::put($attemptsKey, $attempts, now()->addMinutes($lockoutMinutes));
        }

        if ($attempts >= $maxAttempts) {
            $lockedUntil = now()->addMinutes($lockoutMinutes);
            Cache::put($lockoutKey, $lockedUntil->toIso8601String(), $lockedUntil);
            Cache::forget($attemptsKey);
            $this->persistLockout($email, $maxAttempts, $lockedUntil, $context);
        }
    }

    /**
     * Clear the lockout counters for the given email and mark any active log as manually unlocked.
     * Called by the admin "Déverrouiller" action.
     */
    public function unlockAccount(string $email, int $adminUserId): void
    {
        $email = strtolower($email);
        Cache::forget("login_lockout:{$email}");
        Cache::forget("login_attempts:{$email}");

        LoginLockoutLog::where('email', $email)
            ->whereNull('unlocked_at')
            ->where('locked_until', '>', now())
            ->update([
                'unlocked_at' => now(),
                'unlocked_by' => $adminUserId,
            ]);
    }

    /**
     * Clear lockout cache counters after a successful login.
     * Cache-only — does not update any DB record.
     */
    public function clearLockout(string $email): void
    {
        $email = strtolower($email);
        Cache::forget("login_lockout:{$email}");
        Cache::forget("login_attempts:{$email}");
    }

    /**
     * Persist a lockout event to the database for admin visibility.
     * Non-fatal: a write failure does not prevent the lockout from being enforced via cache.
     *
     * @param  array{client_type?: string, ip?: string|null, user_agent?: string|null}  $context
     */
    private function persistLockout(string $email, int $attempts, Carbon $lockedUntil, array $context): void
    {
        try {
            $user = User::where('email', $email)->first();
            LoginLockoutLog::create([
                'email'          => $email,
                'user_id'        => $user?->id,
                'client_type'    => $context['client_type'] ?? 'api',
                'ip_address'     => $context['ip'] ?? null,
                'user_agent'     => mb_substr($context['user_agent'] ?? '', 0, 500),
                'attempts_count' => $attempts,
                'locked_at'      => now(),
                'locked_until'   => $lockedUntil,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('LoginLockoutLog persist failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a failed login attempt: increment counter, trigger lockout if threshold reached.
     *
     * @param  array{client_type?: string, ip?: string|null, user_agent?: string|null}|null  $context
     *
     * @throws AuthException Always throws — either AUTH_ACCOUNT_LOCKED or AUTH_INVALID_CREDENTIALS
     */
    private function handleFailedLoginAttempt(string $email, ?array $context = null): never
    {
        $attemptsKey = "login_attempts:{$email}";
        $lockoutKey = "login_lockout:{$email}";

        $attempts = (int) Cache::increment($attemptsKey);

        // Set TTL on first attempt
        if ($attempts === 1) {
            $lockoutMinutes = (int) config('bookmi.auth.lockout_minutes', 15);
            Cache::put($attemptsKey, $attempts, now()->addMinutes($lockoutMinutes));
        }

        $maxAttempts = (int) config('bookmi.auth.max_login_attempts', 5);

        if ($attempts >= $maxAttempts) {
            $lockoutMinutes = (int) config('bookmi.auth.lockout_minutes', 15);
            $lockedUntil = now()->addMinutes($lockoutMinutes);
            Cache::put($lockoutKey, $lockedUntil->toIso8601String(), $lockedUntil);
            Cache::forget($attemptsKey);
            $this->persistLockout($email, $maxAttempts, $lockedUntil, $context ?? []);

            throw AuthException::accountLocked($lockedUntil->toIso8601String(), $lockoutMinutes * 60);
        }

        throw AuthException::invalidCredentials($maxAttempts - $attempts);
    }

    /**
     * Send a password reset link to the given email.
     */
    public function forgotPassword(string $email): void
    {
        $email = strtolower($email);

        $status = Password::sendResetLink(['email' => $email]);

        if ($status === Password::RESET_THROTTLED) {
            throw AuthException::resetThrottled();
        }

        // RESET_LINK_SENT or INVALID_USER — always return silently (anti-enumeration)
    }

    /**
     * Reset user password using a valid token.
     */
    public function resetPassword(string $email, string $token, string $password): void
    {
        $email = strtolower($email);

        $status = Password::reset(
            ['email' => $email, 'password' => $password, 'password_confirmation' => $password, 'token' => $token],
            function (User $user, string $password) {
                $user->password = $password;
                $user->save();

                $user->tokens()->delete();

                PasswordReset::dispatch($user);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw AuthException::resetTokenInvalid();
        }
    }

    /**
     * Logout user by revoking the current token only.
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();

        UserLoggedOut::dispatch($user);
    }

    /**
     * Get authenticated user profile with roles and permissions.
     *
     * @return array{user: array<string, mixed>, roles: array<int, string>, permissions: array<int, string>}
     */
    public function getProfile(User $user): array
    {
        /** @var \Carbon\Carbon|null $phoneVerifiedAt */
        $phoneVerifiedAt = $user->phone_verified_at;

        return [
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'phone_verified_at' => $phoneVerifiedAt?->toIso8601String(),
                'is_active' => $user->is_active,
                'is_client_verified' => (bool) $user->is_client_verified,
                'avatar_url' => $user->avatar_url,
                'requires_reconsent'   => $user->cgu_version_accepted === null
                    || version_compare((string) $user->cgu_version_accepted, (string) config('bookmi.consent.cgu_version'), '<'),
                'cgu_version_accepted' => $user->cgu_version_accepted,
                'current_cgu_version'  => config('bookmi.consent.cgu_version'),
            ],
            'roles' => $user->getRoleNames()->toArray(),
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
        ];
    }

    /**
     * Update user's name and/or avatar.
     *
     * @param array{first_name?: string, last_name?: string, avatar?: string|null} $data
     */
    public function updateProfile(User $user, array $data): array
    {
        $user->update($data);
        $user->refresh();

        return $this->getProfile($user);
    }

    public function sendOtp(string $phone): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $ttl = (int) config('bookmi.auth.otp_expiration_minutes', 10);
        Cache::put("otp:{$phone}", $code, now()->addMinutes($ttl));

        $this->smsService->sendOtp($phone, $code);

        return $code;
    }
}
