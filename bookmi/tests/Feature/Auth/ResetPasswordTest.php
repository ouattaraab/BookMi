<?php

namespace Tests\Feature\Auth;

use App\Events\PasswordReset;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
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

    private function createResetToken(User $user): string
    {
        return Password::broker()->createToken($user);
    }

    // ── Success Cases ─────────────────────────────────

    #[Test]
    public function valid_token_resets_password(): void
    {
        $user = $this->createVerifiedUser();
        $token = $this->createResetToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.message', 'Votre mot de passe a été réinitialisé avec succès.');

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    #[Test]
    public function reset_revokes_all_sanctum_tokens(): void
    {
        $user = $this->createVerifiedUser();
        $user->createToken('token-1');
        $user->createToken('token-2');

        $this->assertSame(2, $user->tokens()->count());

        $token = $this->createResetToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200);
        $this->assertSame(0, $user->tokens()->count());
    }

    #[Test]
    public function reset_dispatches_password_reset_event(): void
    {
        Event::fake();

        $user = $this->createVerifiedUser();
        $token = $this->createResetToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200);

        Event::assertDispatched(PasswordReset::class, function ($event) {
            return $event->user->email === 'test@example.com';
        });
    }

    // ── Error Cases ─────────────────────────────────

    #[Test]
    public function invalid_token_returns_422(): void
    {
        $this->createVerifiedUser();

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'invalid-token',
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'AUTH_RESET_TOKEN_INVALID');
    }

    #[Test]
    public function expired_token_returns_422(): void
    {
        $user = $this->createVerifiedUser();
        $token = $this->createResetToken($user);

        // Simulate token expiration by modifying created_at
        DB::table('password_reset_tokens')
            ->where('email', 'test@example.com')
            ->update(['created_at' => now()->subMinutes(61)]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'AUTH_RESET_TOKEN_INVALID');
    }

    #[Test]
    public function unknown_email_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'some-token',
            'email' => 'nonexistent@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'AUTH_RESET_TOKEN_INVALID');
    }

    #[Test]
    public function password_confirmation_mismatch_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'some-token',
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');

        $this->assertArrayHasKey('password', $response->json('error.details.errors'));
    }

    // ── Validation Failures ─────────────────────────────────

    #[Test]
    public function missing_token_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');

        $this->assertArrayHasKey('token', $response->json('error.details.errors'));
    }

    #[Test]
    public function missing_email_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'some-token',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');

        $this->assertArrayHasKey('email', $response->json('error.details.errors'));
    }

    #[Test]
    public function missing_password_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'some-token',
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');

        $this->assertArrayHasKey('password', $response->json('error.details.errors'));
    }

    #[Test]
    public function password_too_short_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'some-token',
            'email' => 'test@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');

        $this->assertArrayHasKey('password', $response->json('error.details.errors'));
    }

    // ── Route Middleware ─────────────────────────────────

    #[Test]
    public function reset_password_route_has_throttle_middleware(): void
    {
        $route = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutesByName())
            ->get('api.v1.auth.reset-password');

        $this->assertNotNull($route);
        $this->assertContains('throttle:auth', $route->middleware());
    }
}
