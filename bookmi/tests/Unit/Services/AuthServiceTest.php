<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\AuthService;
use App\Services\SmsService;
use App\Services\TwoFactorService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $authService;

    private SmsService $smsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->smsService = $this->mock(SmsService::class);
        $twoFactorService = $this->mock(TwoFactorService::class);
        $this->authService = new AuthService($this->smsService, $twoFactorService);
    }

    #[Test]
    public function register_creates_user_with_correct_data(): void
    {
        // OTP disabled at registration — phone is auto-verified
        $this->smsService->shouldReceive('sendOtp')->never();

        $user = $this->authService->register([
            'first_name' => 'Aminata',
            'last_name' => 'Koné',
            'email' => 'aminata@example.com',
            'phone' => '+2250700000001',
            'password' => 'secret123',
            'role' => 'client',
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('Aminata', $user->first_name);
        $this->assertSame('Koné', $user->last_name);
        $this->assertSame('aminata@example.com', $user->email);
        $this->assertSame('+2250700000001', $user->phone);
        $this->assertTrue($user->is_active);
        $this->assertNotNull($user->phone_verified_at);
    }

    #[Test]
    public function register_assigns_role_via_spatie(): void
    {
        $this->smsService->shouldReceive('sendOtp')->never();

        $user = $this->authService->register([
            'first_name' => 'DJ',
            'last_name' => 'Kerozen',
            'email' => 'kerozen@example.com',
            'phone' => '+2250700000002',
            'password' => 'secret123',
            'role' => 'talent',
        ]);

        $this->assertTrue($user->hasRole('talent', 'api'));
        $this->assertFalse($user->hasRole('client', 'api'));
    }

    #[Test]
    public function register_generates_otp_in_cache(): void
    {
        // OTP disabled at registration — no OTP stored in cache
        $this->smsService->shouldReceive('sendOtp')->never();

        $this->authService->register([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'phone' => '+2250700000003',
            'password' => 'secret123',
            'role' => 'client',
        ]);

        $cachedOtp = Cache::get('otp:+2250700000003');
        $this->assertNull($cachedOtp);
    }

    #[Test]
    public function register_calls_sms_service_with_otp(): void
    {
        // OTP disabled at registration — SmsService not called
        $this->smsService->shouldReceive('sendOtp')->never();

        $this->authService->register([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test2@example.com',
            'phone' => '+2250700000004',
            'password' => 'secret123',
            'role' => 'client',
        ]);
    }

    #[Test]
    public function send_otp_stores_code_with_correct_ttl(): void
    {
        $this->smsService->shouldReceive('sendOtp')->once();

        $code = $this->authService->sendOtp('+2250700000005');

        $this->assertSame(6, strlen($code));
        $this->assertSame($code, Cache::get('otp:+2250700000005'));
    }
}
