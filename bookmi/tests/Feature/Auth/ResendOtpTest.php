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

class ResendOtpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([ThrottleRequests::class, ThrottleRequestsWithRedis::class]);
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function createUser(array $overrides = []): User
    {
        $user = User::factory()->create(array_merge([
            'phone' => '+2250700000001',
            'phone_verified_at' => null,
            'is_active' => true,
        ], $overrides));

        $user->assignRole('client');

        return $user;
    }

    // ── Success Cases ─────────────────────────────────

    #[Test]
    public function resend_otp_returns_200(): void
    {
        $this->createUser();

        $response = $this->postJson('/api/v1/auth/resend-otp', [
            'phone' => '+2250700000001',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.message', 'Un nouveau code OTP a été envoyé.');
    }

    #[Test]
    public function resend_otp_generates_new_code_in_cache(): void
    {
        $this->createUser();
        Cache::put('otp:+2250700000001', '111111', now()->addMinutes(10));

        $this->postJson('/api/v1/auth/resend-otp', [
            'phone' => '+2250700000001',
        ]);

        $newOtp = Cache::get('otp:+2250700000001');
        $this->assertNotNull($newOtp);
        $this->assertSame(6, strlen($newOtp));
    }

    #[Test]
    public function resend_otp_increments_resend_counter(): void
    {
        $this->createUser();

        $this->postJson('/api/v1/auth/resend-otp', [
            'phone' => '+2250700000001',
        ]);

        $this->assertSame(1, (int) Cache::get('otp_resend_count:+2250700000001'));

        $this->postJson('/api/v1/auth/resend-otp', [
            'phone' => '+2250700000001',
        ]);

        $this->assertSame(2, (int) Cache::get('otp_resend_count:+2250700000001'));
    }

    // ── Error Cases ─────────────────────────────────

    #[Test]
    public function resend_otp_limit_3_per_hour_returns_429(): void
    {
        $this->createUser();
        Cache::put('otp_resend_count:+2250700000001', 3, now()->addHour());

        $response = $this->postJson('/api/v1/auth/resend-otp', [
            'phone' => '+2250700000001',
        ]);

        $response->assertStatus(429)
            ->assertJsonPath('error.code', 'AUTH_OTP_RESEND_LIMIT')
            ->assertJsonPath('error.message', 'Limite de renvoi OTP atteinte. Réessayez dans une heure.');
    }

    #[Test]
    public function resend_otp_phone_not_registered_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/resend-otp', [
            'phone' => '+2250700099999',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');

        $this->assertArrayHasKey('phone', $response->json('error.details.errors'));
    }

    // ── Validation Failures ─────────────────────────────────

    #[Test]
    public function resend_otp_missing_phone_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/resend-otp', []);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');

        $this->assertArrayHasKey('phone', $response->json('error.details.errors'));
    }

    #[Test]
    public function resend_otp_invalid_phone_format_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/resend-otp', [
            'phone' => '0700000001',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');

        $this->assertArrayHasKey('phone', $response->json('error.details.errors'));
    }

    // ── Route Middleware ─────────────────────────────────

    #[Test]
    public function resend_otp_route_has_throttle_middleware(): void
    {
        $route = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutesByName())
            ->get('api.v1.auth.resend-otp');

        $this->assertNotNull($route);
        $this->assertContains('throttle:auth', $route->middleware());
    }
}
