<?php

namespace Tests\Feature\Auth;

use App\Events\UserLoggedOut;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([ThrottleRequests::class, ThrottleRequestsWithRedis::class]);
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function createAuthenticatedUser(): array
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
            'phone' => '+2250700000001',
            'phone_verified_at' => now(),
            'is_active' => true,
        ]);
        $user->assignRole('client');

        $expirationHours = (int) config('bookmi.auth.token_expiration_hours', 24);
        $token = $user->createToken('auth-token', expiresAt: now()->addHours($expirationHours))->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    // ── Success Cases ─────────────────────────────────

    #[Test]
    public function logout_returns_200_with_confirmation_message(): void
    {
        ['user' => $user, 'token' => $token] = $this->createAuthenticatedUser();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJsonPath('data.message', 'Déconnexion réussie.');
    }

    #[Test]
    public function logout_revokes_current_token(): void
    {
        ['user' => $user, 'token' => $token] = $this->createAuthenticatedUser();

        // Capture token ID before logout
        $tokenId = $user->tokens()->first()->id;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(200);

        // Specific token should be deleted from the database
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);

        // Reset auth guard to clear cached user, then verify 401
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/me')
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    #[Test]
    public function logout_does_not_revoke_other_tokens(): void
    {
        ['user' => $user, 'token' => $token1] = $this->createAuthenticatedUser();

        $expirationHours = (int) config('bookmi.auth.token_expiration_hours', 24);
        $token2 = $user->createToken('auth-token-2', expiresAt: now()->addHours($expirationHours))->plainTextToken;

        // Logout with token1
        $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(200);

        // token2 should still work
        $this->withHeader('Authorization', 'Bearer ' . $token2)
            ->getJson('/api/v1/me')
            ->assertStatus(200);
    }

    #[Test]
    public function logout_dispatches_user_logged_out_event(): void
    {
        Event::fake();

        ['user' => $user, 'token' => $token] = $this->createAuthenticatedUser();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);

        Event::assertDispatched(UserLoggedOut::class, function ($event) {
            return $event->user->email === 'test@example.com';
        });
    }

    // ── Error Cases ─────────────────────────────────

    #[Test]
    public function logout_without_token_returns_401(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401)
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    #[Test]
    public function logout_with_expired_token_returns_401(): void
    {
        ['user' => $user] = $this->createAuthenticatedUser();

        $expiredToken = $user->createToken('expired-token', expiresAt: now()->subMinute())->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $expiredToken)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(401)
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    #[Test]
    public function logout_with_invalid_token_returns_401(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token')
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(401)
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    // ── Route Middleware ─────────────────────────────────

    #[Test]
    public function logout_route_has_throttle_and_sanctum_middleware(): void
    {
        $route = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutesByName())
            ->get('api.v1.auth.logout');

        $this->assertNotNull($route);
        $this->assertContains('auth:sanctum', $route->middleware());
        $this->assertContains('throttle:auth', $route->middleware());
    }
}
