<?php

namespace App\Services;

use App\Events\PasswordReset;
use App\Events\UserLoggedIn;
use App\Events\UserLoggedOut;
use App\Exceptions\AuthException;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthService
{
    public function __construct(
        private readonly SmsService $smsService,
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
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'is_active' => true,
            ]);

            $user->assignRole($data['role']);

            $this->sendOtp($user->phone);

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
     * @return array{token: string, user: array<string, mixed>, roles: array<int, string>}
     */
    public function login(string $email, string $password): array
    {
        $email = strtolower($email);
        $lockoutKey = "login_lockout:{$email}";

        // 1. Check lockout (atomic — no Cache::has)
        $lockedUntilIso = Cache::get($lockoutKey);
        if ($lockedUntilIso !== null) {
            $lockedUntil = \Carbon\Carbon::parse($lockedUntilIso);
            $remainingSeconds = (int) max(0, now()->diffInSeconds($lockedUntil, false));

            throw AuthException::accountLocked($lockedUntilIso, $remainingSeconds);
        }

        // 2. Find user by email (same error for not found and wrong password — prevents enumeration)
        $user = User::where('email', $email)->first();
        if (! $user) {
            $this->handleFailedLoginAttempt($email);
        }

        // 3. Verify password
        if (! Hash::check($password, $user->password)) {
            $this->handleFailedLoginAttempt($email);
        }

        // 4. Check phone verified
        if ($user->phone_verified_at === null) {
            throw AuthException::phoneNotVerified();
        }

        // 5. Check account active
        if (! $user->is_active) {
            throw AuthException::accountDisabled();
        }

        // 6. Success — clear counters, create token, dispatch event
        Cache::forget("login_attempts:{$email}");
        Cache::forget($lockoutKey);

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
            ],
            'roles' => $user->getRoleNames()->toArray(),
        ];
    }

    /**
     * Handle a failed login attempt: increment counter, trigger lockout if threshold reached.
     *
     * @throws AuthException Always throws — either AUTH_ACCOUNT_LOCKED or AUTH_INVALID_CREDENTIALS
     */
    private function handleFailedLoginAttempt(string $email): never
    {
        $attemptsKey = "login_attempts:{$email}";
        $lockoutKey = "login_lockout:{$email}";

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
            ],
            'roles' => $user->getRoleNames()->toArray(),
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
        ];
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
