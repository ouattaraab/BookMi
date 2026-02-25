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

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([ThrottleRequests::class, ThrottleRequestsWithRedis::class]);
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function validClientData(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Aminata',
            'last_name' => 'Koné',
            'email' => 'aminata@example.com',
            'phone' => '+2250700000001',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'client',
        ], $overrides);
    }

    private function validTalentData(array $overrides = []): array
    {
        $category = \App\Models\Category::factory()->create();

        return array_merge([
            'first_name' => 'DJ',
            'last_name' => 'Kerozen',
            'email' => 'kerozen@example.com',
            'phone' => '+2250700000002',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'talent',
            'category_id' => $category->id,
        ], $overrides);
    }

    // ── Success Cases ─────────────────────────────────

    #[Test]
    public function client_can_register_successfully(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->validClientData());

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['token', 'user', 'roles']]);

        $this->assertDatabaseHas('users', [
            'email' => 'aminata@example.com',
            'first_name' => 'Aminata',
            'last_name' => 'Koné',
            'phone' => '+2250700000001',
        ]);

        $user = User::where('email', 'aminata@example.com')->first();
        $this->assertTrue($user->hasRole('client', 'api'));
        $this->assertTrue($user->is_active);
        // OTP disabled at registration — phone is auto-verified
        $this->assertNotNull($user->phone_verified_at);
    }

    #[Test]
    public function talent_can_register_successfully(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->validTalentData());

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['token', 'user', 'roles']]);

        $user = User::where('email', 'kerozen@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('talent', 'api'));
        $this->assertTrue($user->is_active);
        // OTP disabled at registration — phone is auto-verified
        $this->assertNotNull($user->phone_verified_at);
    }

    #[Test]
    public function registration_does_not_generate_otp_in_cache(): void
    {
        // OTP is disabled at registration — phone is auto-verified, no OTP stored
        $this->postJson('/api/v1/auth/register', $this->validClientData());

        $otp = Cache::get('otp:+2250700000001');
        $this->assertNull($otp);
    }

    #[Test]
    public function password_is_hashed(): void
    {
        $this->postJson('/api/v1/auth/register', $this->validClientData());

        $user = User::where('email', 'aminata@example.com')->first();
        $this->assertNotSame('password123', $user->password);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('password123', $user->password));
    }

    // ── Validation Failures ───────────────────────────

    #[Test]
    public function registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'aminata@example.com']);

        $response = $this->postJson('/api/v1/auth/register', $this->validClientData());

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonPath('error.details.errors.email.0', 'Cette adresse email est déjà utilisée.');
    }

    #[Test]
    public function registration_fails_with_duplicate_phone(): void
    {
        User::factory()->create(['phone' => '+2250700000001']);

        $response = $this->postJson('/api/v1/auth/register', $this->validClientData());

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonPath('error.details.errors.phone.0', 'Ce numéro de téléphone est déjà utilisé.');
    }

    #[Test]
    public function registration_fails_with_short_password(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->validClientData([
            'password' => 'short',
            'password_confirmation' => 'short',
        ]));

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');

        $this->assertArrayHasKey('password', $response->json('error.details.errors'));
    }

    #[Test]
    public function registration_fails_with_invalid_role(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->validClientData([
            'role' => 'admin_ceo',
        ]));

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonPath('error.details.errors.role.0', 'Le rôle doit être client ou talent.');
    }

    #[Test]
    public function registration_fails_with_missing_required_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');

        $errors = $response->json('error.details.errors');
        $this->assertArrayHasKey('first_name', $errors);
        $this->assertArrayHasKey('last_name', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('phone', $errors);
        $this->assertArrayHasKey('password', $errors);
        $this->assertArrayHasKey('role', $errors);
    }

    #[Test]
    public function registration_fails_with_invalid_phone_format(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->validClientData([
            'phone' => '0700000001',
        ]));

        $response->assertStatus(422)
            ->assertJsonPath('error.details.errors.phone.0', 'Le numéro de téléphone doit être au format +225 suivi de 10 chiffres.');
    }

    #[Test]
    public function registration_fails_with_phone_wrong_country_code(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->validClientData([
            'phone' => '+33600000001',
        ]));

        $response->assertStatus(422);
        $this->assertArrayHasKey('phone', $response->json('error.details.errors'));
    }

    #[Test]
    public function talent_registration_fails_without_category(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'DJ',
            'last_name' => 'Kerozen',
            'email' => 'kerozen@example.com',
            'phone' => '+2250700000002',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'talent',
        ]);

        $response->assertStatus(422);
        $this->assertArrayHasKey('category_id', $response->json('error.details.errors'));
    }

    #[Test]
    public function registration_fails_with_password_mismatch(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->validClientData([
            'password_confirmation' => 'different123',
        ]));

        $response->assertStatus(422);
        $this->assertArrayHasKey('password', $response->json('error.details.errors'));
    }

    #[Test]
    public function validation_messages_are_in_french(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->validClientData([
            'email' => 'not-an-email',
        ]));

        $response->assertStatus(422);
        $errors = $response->json('error.details.errors.email');
        $this->assertNotEmpty($errors);
        $this->assertSame("L'adresse email n'est pas valide.", $errors[0]);
    }

    #[Test]
    public function register_route_has_throttle_middleware(): void
    {
        $route = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutesByName())
            ->get('api.v1.auth.register');

        $this->assertNotNull($route);
        $this->assertContains('throttle:auth', $route->middleware());
    }
}
