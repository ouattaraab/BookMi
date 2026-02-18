<?php

namespace Tests\Unit\Services;

use App\Events\UserLoggedIn;
use App\Exceptions\AuthException;
use App\Models\User;
use App\Services\AuthService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthServiceLoginTest extends TestCase
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

    // ── login() ─────────────────────────────────

    #[Test]
    public function login_returns_token_user_and_roles(): void
    {
        $user = $this->createVerifiedUser();

        $result = $this->authService->login('test@example.com', 'password123');

        $this->assertArrayHasKey('token', $result);
        $this->assertNotEmpty($result['token']);
        $this->assertArrayHasKey('user', $result);
        $this->assertSame($user->id, $result['user']['id']);
        $this->assertSame('test@example.com', $result['user']['email']);
        $this->assertArrayHasKey('roles', $result);
        $this->assertContains('client', $result['roles']);
    }

    #[Test]
    public function login_dispatches_user_logged_in_event(): void
    {
        Event::fake();

        $this->createVerifiedUser();

        $this->authService->login('test@example.com', 'password123');

        Event::assertDispatched(UserLoggedIn::class, function ($event) {
            return $event->user->email === 'test@example.com';
        });
    }

    #[Test]
    public function login_increments_attempts_on_wrong_password(): void
    {
        $this->createVerifiedUser();

        try {
            $this->authService->login('test@example.com', 'wrong-password');
        } catch (AuthException $e) {
            $this->assertSame('AUTH_INVALID_CREDENTIALS', $e->getErrorCode());
        }

        $this->assertSame(1, (int) Cache::get('login_attempts:test@example.com'));
    }

    #[Test]
    public function login_increments_attempts_on_unknown_email(): void
    {
        try {
            $this->authService->login('unknown@example.com', 'password123');
        } catch (AuthException $e) {
            $this->assertSame('AUTH_INVALID_CREDENTIALS', $e->getErrorCode());
        }

        $this->assertSame(1, (int) Cache::get('login_attempts:unknown@example.com'));
    }

    #[Test]
    public function login_clears_counters_after_success(): void
    {
        $this->createVerifiedUser();
        Cache::put('login_attempts:test@example.com', 2, now()->addMinutes(15));

        $this->authService->login('test@example.com', 'password123');

        $this->assertNull(Cache::get('login_attempts:test@example.com'));
        $this->assertNull(Cache::get('login_lockout:test@example.com'));
    }

    #[Test]
    public function login_locks_account_after_max_attempts(): void
    {
        $this->createVerifiedUser();
        Cache::put('login_attempts:test@example.com', 4, now()->addMinutes(15));

        try {
            $this->authService->login('test@example.com', 'wrong-password');
        } catch (AuthException $e) {
            $this->assertSame('AUTH_ACCOUNT_LOCKED', $e->getErrorCode());
        }

        $this->assertNotNull(Cache::get('login_lockout:test@example.com'));
        $this->assertNull(Cache::get('login_attempts:test@example.com'));
    }

    #[Test]
    public function login_throws_phone_not_verified(): void
    {
        $this->createVerifiedUser(['phone_verified_at' => null]);

        $this->expectException(AuthException::class);

        try {
            $this->authService->login('test@example.com', 'password123');
        } catch (AuthException $e) {
            $this->assertSame('AUTH_PHONE_NOT_VERIFIED', $e->getErrorCode());
            // Must NOT increment login attempts
            $this->assertNull(Cache::get('login_attempts:test@example.com'));

            throw $e;
        }
    }

    #[Test]
    public function login_throws_account_disabled(): void
    {
        $this->createVerifiedUser(['is_active' => false]);

        $this->expectException(AuthException::class);

        try {
            $this->authService->login('test@example.com', 'password123');
        } catch (AuthException $e) {
            $this->assertSame('AUTH_ACCOUNT_DISABLED', $e->getErrorCode());
            // Must NOT increment login attempts
            $this->assertNull(Cache::get('login_attempts:test@example.com'));

            throw $e;
        }
    }
}
