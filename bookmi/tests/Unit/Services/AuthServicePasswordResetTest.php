<?php

namespace Tests\Unit\Services;

use App\Events\PasswordReset;
use App\Exceptions\AuthException;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Services\AuthService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthServicePasswordResetTest extends TestCase
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

    // ── forgotPassword() ─────────────────────────────────

    #[Test]
    public function forgot_password_sends_notification_to_existing_user(): void
    {
        Notification::fake();

        $user = $this->createVerifiedUser();

        $this->authService->forgotPassword('test@example.com');

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    #[Test]
    public function forgot_password_does_not_throw_for_unknown_email(): void
    {
        Notification::fake();

        // Should NOT throw — anti-enumeration
        $this->authService->forgotPassword('nonexistent@example.com');

        Notification::assertNothingSent();
    }

    #[Test]
    public function forgot_password_normalizes_email_to_lowercase(): void
    {
        Notification::fake();

        $user = $this->createVerifiedUser();

        $this->authService->forgotPassword('TEST@EXAMPLE.COM');

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    #[Test]
    public function forgot_password_throws_throttled_on_rapid_requests(): void
    {
        Notification::fake();

        $this->createVerifiedUser();

        // First request — succeeds
        $this->authService->forgotPassword('test@example.com');

        // Second request within 60 seconds — throttled by broker
        $this->expectException(AuthException::class);

        try {
            $this->authService->forgotPassword('test@example.com');
        } catch (AuthException $e) {
            $this->assertSame('AUTH_RESET_THROTTLED', $e->getErrorCode());

            throw $e;
        }
    }

    // ── resetPassword() ─────────────────────────────────

    #[Test]
    public function reset_password_updates_user_password(): void
    {
        $user = $this->createVerifiedUser();
        $token = Password::broker()->createToken($user);

        $this->authService->resetPassword('test@example.com', $token, 'newpassword123');

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    #[Test]
    public function reset_password_revokes_all_sanctum_tokens(): void
    {
        $user = $this->createVerifiedUser();
        $user->createToken('token-1');
        $user->createToken('token-2');

        $this->assertSame(2, $user->tokens()->count());

        $token = Password::broker()->createToken($user);

        $this->authService->resetPassword('test@example.com', $token, 'newpassword123');

        $this->assertSame(0, $user->tokens()->count());
    }

    #[Test]
    public function reset_password_dispatches_password_reset_event(): void
    {
        Event::fake();

        $user = $this->createVerifiedUser();
        $token = Password::broker()->createToken($user);

        $this->authService->resetPassword('test@example.com', $token, 'newpassword123');

        Event::assertDispatched(PasswordReset::class, function ($event) {
            return $event->user->email === 'test@example.com';
        });
    }

    #[Test]
    public function reset_password_throws_on_invalid_token(): void
    {
        $this->createVerifiedUser();

        $this->expectException(AuthException::class);

        try {
            $this->authService->resetPassword('test@example.com', 'invalid-token', 'newpassword123');
        } catch (AuthException $e) {
            $this->assertSame('AUTH_RESET_TOKEN_INVALID', $e->getErrorCode());

            throw $e;
        }
    }

    #[Test]
    public function reset_password_throws_on_unknown_email(): void
    {
        $this->expectException(AuthException::class);

        try {
            $this->authService->resetPassword('nonexistent@example.com', 'some-token', 'newpassword123');
        } catch (AuthException $e) {
            $this->assertSame('AUTH_RESET_TOKEN_INVALID', $e->getErrorCode());

            throw $e;
        }
    }
}
