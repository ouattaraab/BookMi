<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([ThrottleRequests::class, ThrottleRequestsWithRedis::class]);
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function createVerifiedUser(array $overrides = []): User
    {
        $user = User::factory()->create(array_merge([
            'email' => 'test@example.com',
            'password' => 'password123',
            'phone' => '+2250700000001',
            'phone_verified_at' => now(),
            'is_active' => true,
        ], $overrides));

        $user->assignRole('client');

        return $user;
    }

    // ── Success Cases ─────────────────────────────────

    #[Test]
    public function valid_email_returns_200_and_sends_notification(): void
    {
        Notification::fake();

        $user = $this->createVerifiedUser();

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.message', 'Si cette adresse email est associée à un compte, un lien de réinitialisation a été envoyé.');

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    #[Test]
    public function unknown_email_returns_200_anti_enumeration(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.message', 'Si cette adresse email est associée à un compte, un lien de réinitialisation a été envoyé.');

        Notification::assertNothingSent();
    }

    #[Test]
    public function email_is_case_insensitive(): void
    {
        Notification::fake();

        $user = $this->createVerifiedUser();

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'TEST@EXAMPLE.COM',
        ]);

        $response->assertStatus(200);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    #[Test]
    public function broker_throttle_returns_429_on_rapid_requests(): void
    {
        Notification::fake();

        $this->createVerifiedUser();

        // First request — succeeds
        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'test@example.com',
        ])->assertStatus(200);

        // Second request within 60 seconds — broker throttle
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(429)
            ->assertJsonPath('error.code', 'AUTH_RESET_THROTTLED');
    }

    // ── Validation Failures ─────────────────────────────────

    #[Test]
    public function missing_email_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', []);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');

        $this->assertArrayHasKey('email', $response->json('error.details.errors'));
    }

    #[Test]
    public function invalid_email_format_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');

        $this->assertArrayHasKey('email', $response->json('error.details.errors'));
    }

    // ── Route Middleware ─────────────────────────────────

    #[Test]
    public function forgot_password_route_has_throttle_middleware(): void
    {
        $route = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutesByName())
            ->get('api.v1.auth.forgot-password');

        $this->assertNotNull($route);
        $this->assertContains('throttle:forgot-password', $route->middleware());
    }
}
