<?php

namespace Tests\Unit\Services;

use App\Exceptions\AuthException;
use App\Models\User;
use App\Services\AuthService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthServiceVerifyOtpTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->authService = app(AuthService::class);
    }

    private function createUserWithOtp(string $phone = '+2250700000001'): User
    {
        $user = User::factory()->create([
            'phone' => $phone,
            'phone_verified_at' => null,
            'is_active' => true,
        ]);

        $user->assignRole('client');

        Cache::put("otp:{$phone}", '123456', now()->addMinutes(10));

        return $user;
    }

    // ── verifyOtp() ─────────────────────────────────

    #[Test]
    public function verify_otp_returns_token_user_and_roles(): void
    {
        $user = $this->createUserWithOtp();

        $result = $this->authService->verifyOtp('+2250700000001', '123456');

        $this->assertArrayHasKey('token', $result);
        $this->assertNotEmpty($result['token']);
        $this->assertArrayHasKey('user', $result);
        $this->assertSame($user->id, $result['user']['id']);
        $this->assertArrayHasKey('roles', $result);
        $this->assertContains('client', $result['roles']);
    }

    #[Test]
    public function verify_otp_clears_cache_after_success(): void
    {
        $this->createUserWithOtp();
        Cache::put('otp_attempts:+2250700000001', 2, now()->addMinutes(15));
        Cache::put('otp_resend_count:+2250700000001', 1, now()->addHour());

        $this->authService->verifyOtp('+2250700000001', '123456');

        $this->assertNull(Cache::get('otp:+2250700000001'));
        $this->assertNull(Cache::get('otp_attempts:+2250700000001'));
        $this->assertNull(Cache::get('otp_resend_count:+2250700000001'));
    }

    #[Test]
    public function verify_otp_throws_expired_when_no_otp_in_cache(): void
    {
        $this->createUserWithOtp();
        Cache::forget('otp:+2250700000001');

        $this->expectException(AuthException::class);

        try {
            $this->authService->verifyOtp('+2250700000001', '123456');
        } catch (AuthException $e) {
            $this->assertSame('AUTH_OTP_EXPIRED', $e->getErrorCode());

            throw $e;
        }
    }

    #[Test]
    public function verify_otp_increments_attempts_on_invalid_code(): void
    {
        $this->createUserWithOtp();

        try {
            $this->authService->verifyOtp('+2250700000001', '999999');
        } catch (AuthException $e) {
            $this->assertSame('AUTH_OTP_INVALID', $e->getErrorCode());
        }

        $this->assertSame(1, (int) Cache::get('otp_attempts:+2250700000001'));
    }

    #[Test]
    public function verify_otp_locks_account_after_max_attempts(): void
    {
        $this->createUserWithOtp();
        Cache::put('otp_attempts:+2250700000001', 4, now()->addMinutes(15));

        try {
            $this->authService->verifyOtp('+2250700000001', '999999');
        } catch (AuthException $e) {
            $this->assertSame('AUTH_ACCOUNT_LOCKED', $e->getErrorCode());
        }

        $this->assertTrue(Cache::has('otp_lockout:+2250700000001'));
        $this->assertNull(Cache::get('otp_attempts:+2250700000001'));
        $this->assertNull(Cache::get('otp:+2250700000001'));
    }

    #[Test]
    public function verify_otp_throws_locked_when_account_is_locked(): void
    {
        $this->createUserWithOtp();
        Cache::put('otp_lockout:+2250700000001', now()->addMinutes(15)->toIso8601String(), now()->addMinutes(15));

        $this->expectException(AuthException::class);

        try {
            $this->authService->verifyOtp('+2250700000001', '123456');
        } catch (AuthException $e) {
            $this->assertSame('AUTH_ACCOUNT_LOCKED', $e->getErrorCode());

            throw $e;
        }
    }

    // ── resendOtp() ─────────────────────────────────

    #[Test]
    public function resend_otp_increments_resend_counter(): void
    {
        $this->createUserWithOtp();

        $this->authService->resendOtp('+2250700000001');

        $this->assertSame(1, (int) Cache::get('otp_resend_count:+2250700000001'));
    }

    #[Test]
    public function resend_otp_throws_limit_exceeded(): void
    {
        $this->createUserWithOtp();
        Cache::put('otp_resend_count:+2250700000001', 3, now()->addHour());

        $this->expectException(AuthException::class);

        try {
            $this->authService->resendOtp('+2250700000001');
        } catch (AuthException $e) {
            $this->assertSame('AUTH_OTP_RESEND_LIMIT', $e->getErrorCode());

            throw $e;
        }
    }
}
