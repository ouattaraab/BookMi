<?php

namespace Tests\Unit\Services;

use App\Events\UserLoggedOut;
use App\Models\User;
use App\Services\AuthService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthServiceLogoutMeTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->authService = app(AuthService::class);
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

    // ── logout() ─────────────────────────────────

    #[Test]
    public function logout_revokes_current_token(): void
    {
        $user = $this->createVerifiedUser();

        $newToken = $user->createToken('auth-token', expiresAt: now()->addHours(24));
        $accessToken = $newToken->accessToken;

        $user->withAccessToken($accessToken);

        $this->authService->logout($user);

        $this->assertNull($accessToken->fresh());
    }

    #[Test]
    public function logout_does_not_revoke_other_tokens(): void
    {
        $user = $this->createVerifiedUser();

        $token1 = $user->createToken('session-1', expiresAt: now()->addHours(24));
        $token2 = $user->createToken('session-2', expiresAt: now()->addHours(24));

        $user->withAccessToken($token1->accessToken);

        $this->authService->logout($user);

        $this->assertNull($token1->accessToken->fresh());
        $this->assertNotNull($token2->accessToken->fresh());
    }

    #[Test]
    public function logout_dispatches_user_logged_out_event(): void
    {
        Event::fake();

        $user = $this->createVerifiedUser();

        $newToken = $user->createToken('auth-token', expiresAt: now()->addHours(24));
        $user->withAccessToken($newToken->accessToken);

        $this->authService->logout($user);

        Event::assertDispatched(UserLoggedOut::class, function ($event) {
            return $event->user->email === 'test@example.com';
        });
    }

    // ── getProfile() ─────────────────────────────────

    #[Test]
    public function get_profile_returns_correct_user_data(): void
    {
        $user = $this->createVerifiedUser();

        $result = $this->authService->getProfile($user);

        $this->assertArrayHasKey('user', $result);
        $this->assertSame($user->id, $result['user']['id']);
        $this->assertSame('test@example.com', $result['user']['email']);
        $this->assertSame('+2250700000001', $result['user']['phone']);
        $this->assertTrue($result['user']['is_active']);
        $this->assertNotNull($result['user']['phone_verified_at']);
    }

    #[Test]
    public function get_profile_includes_roles(): void
    {
        $user = $this->createVerifiedUser();

        $result = $this->authService->getProfile($user);

        $this->assertArrayHasKey('roles', $result);
        $this->assertContains('client', $result['roles']);
    }

    #[Test]
    public function get_profile_includes_permissions(): void
    {
        $user = $this->createVerifiedUser();

        $result = $this->authService->getProfile($user);

        $this->assertArrayHasKey('permissions', $result);
        $this->assertIsArray($result['permissions']);
    }
}
