<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VerifyOtpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([ThrottleRequests::class, ThrottleRequestsWithRedis::class]);
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function createUserWithOtp(array $overrides = []): User
    {
        $user = User::factory()->create(array_merge([
            'phone' => '+2250700000001',
            'phone_verified_at' => null,
            'is_active' => true,
        ], $overrides));

        $user->assignRole('client');

        Cache::put('otp:+2250700000001', '123456', now()->addMinutes(10));

        return $user;
    }

    // ── Success Cases ─────────────────────────────────

    #[Test]
    public function valid_otp_returns_token_and_user(): void
    {
        $user = $this->createUserWithOtp();

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '+2250700000001',
            'code' => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => ['id', 'first_name', 'last_name', 'email', 'phone', 'phone_verified_at', 'is_active'],
                    'roles',
                ],
            ])
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.phone', '+2250700000001')
            ->assertJsonPath('data.roles.0', 'client');
    }

    #[Test]
    public function phone_verified_at_is_updated_after_successful_verification(): void
    {
        $this->createUserWithOtp();

        $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '+2250700000001',
            'code' => '123456',
        ]);

        $user = User::where('phone', '+2250700000001')->first();
        $this->assertNotNull($user->phone_verified_at);
    }

    #[Test]
    public function otp_and_counters_are_cleared_after_successful_verification(): void
    {
        $this->createUserWithOtp();
        Cache::put('otp_attempts:+2250700000001', 2, now()->addMinutes(15));
        Cache::put('otp_resend_count:+2250700000001', 1, now()->addHour());

        $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '+2250700000001',
            'code' => '123456',
        ]);

        $this->assertNull(Cache::get('otp:+2250700000001'));
        $this->assertNull(Cache::get('otp_attempts:+2250700000001'));
        $this->assertNull(Cache::get('otp_resend_count:+2250700000001'));
    }

    #[Test]
    public function verification_works_after_lockout_expires(): void
    {
        $this->createUserWithOtp();

        // Simulate expired lockout
        Cache::put('otp_lockout:+2250700000001', now()->subSecond(), now()->subSecond());

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '+2250700000001',
            'code' => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['token']]);
    }

    // ── Error Cases ─────────────────────────────────

    #[Test]
    public function expired_otp_returns_422(): void
    {
        $this->createUserWithOtp();
        Cache::forget('otp:+2250700000001'); // Simulate expiration

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '+2250700000001',
            'code' => '123456',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'AUTH_OTP_EXPIRED')
            ->assertJsonPath('error.message', 'Le code OTP a expiré. Demandez un nouveau code.');
    }

    #[Test]
    public function invalid_otp_returns_422(): void
    {
        $this->createUserWithOtp();

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '+2250700000001',
            'code' => '999999',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'AUTH_OTP_INVALID')
            ->assertJsonPath('error.message', 'Le code OTP est invalide.')
            ->assertJsonPath('error.details.remaining_attempts', 4);
    }

    #[Test]
    public function lockout_after_5_failed_attempts(): void
    {
        $this->createUserWithOtp();

        // Simulate 4 prior attempts
        Cache::put('otp_attempts:+2250700000001', 4, now()->addMinutes(15));

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '+2250700000001',
            'code' => '999999',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'AUTH_ACCOUNT_LOCKED');

        $this->assertArrayHasKey('locked_until', $response->json('error.details'));
        $this->assertArrayHasKey('remaining_seconds', $response->json('error.details'));

        // OTP and attempts should be cleared
        $this->assertNull(Cache::get('otp:+2250700000001'));
        $this->assertNull(Cache::get('otp_attempts:+2250700000001'));
        $this->assertTrue(Cache::has('otp_lockout:+2250700000001'));
    }

    #[Test]
    public function locked_account_returns_422(): void
    {
        $this->createUserWithOtp();
        Cache::put('otp_lockout:+2250700000001', now()->addMinutes(15)->toIso8601String(), now()->addMinutes(15));

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '+2250700000001',
            'code' => '123456',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'AUTH_ACCOUNT_LOCKED');
    }

    // ── Validation Failures ─────────────────────────────────

    #[Test]
    public function non_digit_code_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '+2250700000001',
            'code' => 'abcdef',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');

        $this->assertArrayHasKey('code', $response->json('error.details.errors'));
    }

    #[Test]
    public function missing_phone_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'code' => '123456',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');

        $this->assertArrayHasKey('phone', $response->json('error.details.errors'));
    }

    #[Test]
    public function missing_code_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '+2250700000001',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');

        $this->assertArrayHasKey('code', $response->json('error.details.errors'));
    }

    #[Test]
    public function invalid_phone_format_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '0700000001',
            'code' => '123456',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');

        $this->assertArrayHasKey('phone', $response->json('error.details.errors'));
    }

    #[Test]
    public function code_wrong_length_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '+2250700000001',
            'code' => '12345',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');

        $this->assertArrayHasKey('code', $response->json('error.details.errors'));
    }

    // ── Route Middleware ─────────────────────────────────

    #[Test]
    public function verify_otp_route_has_throttle_middleware(): void
    {
        $route = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutesByName())
            ->get('api.v1.auth.verify-otp');

        $this->assertNotNull($route);
        $this->assertContains('throttle:auth', $route->middleware());
    }
}
