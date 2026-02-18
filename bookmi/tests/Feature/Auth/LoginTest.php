<?php

namespace Tests\Feature\Auth;

use App\Events\UserLoggedIn;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginTest extends TestCase
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
    public function valid_credentials_return_token_and_user(): void
    {
        $user = $this->createVerifiedUser();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
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
            ->assertJsonPath('data.user.email', 'test@example.com')
            ->assertJsonPath('data.roles.0', 'client');
    }

    #[Test]
    public function login_dispatches_user_logged_in_event(): void
    {
        Event::fake();

        $this->createVerifiedUser();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        Event::assertDispatched(UserLoggedIn::class, function ($event) {
            return $event->user->email === 'test@example.com';
        });
    }

    #[Test]
    public function login_works_after_lockout_expires(): void
    {
        $this->createVerifiedUser();

        // Simulate expired lockout
        Cache::put('login_lockout:test@example.com', now()->subSecond()->toIso8601String(), now()->subSecond());

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['token']]);
    }

    // ── Error Cases ─────────────────────────────────

    #[Test]
    public function invalid_password_returns_422(): void
    {
        $this->createVerifiedUser();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'AUTH_INVALID_CREDENTIALS')
            ->assertJsonPath('error.message', 'Identifiants invalides.')
            ->assertJsonPath('error.details.remaining_attempts', 4);
    }

    #[Test]
    public function unknown_email_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'AUTH_INVALID_CREDENTIALS');
    }

    #[Test]
    public function lockout_after_5_failed_attempts(): void
    {
        $this->createVerifiedUser();

        // Simulate 4 prior failed attempts
        Cache::put('login_attempts:test@example.com', 4, now()->addMinutes(15));

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'AUTH_ACCOUNT_LOCKED');

        $this->assertArrayHasKey('locked_until', $response->json('error.details'));
        $this->assertArrayHasKey('remaining_seconds', $response->json('error.details'));

        // Attempts should be cleared, lockout should exist
        $this->assertNull(Cache::get('login_attempts:test@example.com'));
        $this->assertNotNull(Cache::get('login_lockout:test@example.com'));
    }

    #[Test]
    public function locked_account_returns_422(): void
    {
        $this->createVerifiedUser();
        Cache::put('login_lockout:test@example.com', now()->addMinutes(15)->toIso8601String(), now()->addMinutes(15));

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'AUTH_ACCOUNT_LOCKED');
    }

    #[Test]
    public function phone_not_verified_returns_422(): void
    {
        $this->createVerifiedUser([
            'phone_verified_at' => null,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'AUTH_PHONE_NOT_VERIFIED')
            ->assertJsonPath('error.message', 'Veuillez vérifier votre numéro de téléphone avant de vous connecter.');

        // AC#4: ne pas incrémenter les tentatives
        $this->assertNull(Cache::get('login_attempts:test@example.com'));
    }

    #[Test]
    public function disabled_account_returns_422(): void
    {
        $this->createVerifiedUser([
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'AUTH_ACCOUNT_DISABLED')
            ->assertJsonPath('error.message', 'Ce compte a été désactivé.');

        // AC#5: ne pas incrémenter les tentatives
        $this->assertNull(Cache::get('login_attempts:test@example.com'));
    }

    // ── Validation Failures ─────────────────────────────────

    #[Test]
    public function missing_email_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');

        $this->assertArrayHasKey('email', $response->json('error.details.errors'));
    }

    #[Test]
    public function missing_password_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');

        $this->assertArrayHasKey('password', $response->json('error.details.errors'));
    }

    #[Test]
    public function invalid_email_format_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'not-an-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');

        $this->assertArrayHasKey('email', $response->json('error.details.errors'));
    }

    // ── Route Middleware ─────────────────────────────────

    #[Test]
    public function login_route_has_throttle_middleware(): void
    {
        $route = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutesByName())
            ->get('api.v1.auth.login');

        $this->assertNotNull($route);
        $this->assertContains('throttle:auth', $route->middleware());
    }
}
