<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MeTest extends TestCase
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
    public function me_returns_200_with_user_profile(): void
    {
        $user = $this->createVerifiedUser();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'first_name', 'last_name', 'email', 'phone', 'phone_verified_at', 'is_active'],
                    'roles',
                    'permissions',
                ],
            ])
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.first_name', $user->first_name)
            ->assertJsonPath('data.user.last_name', $user->last_name)
            ->assertJsonPath('data.user.email', 'test@example.com')
            ->assertJsonPath('data.user.phone', '+2250700000001')
            ->assertJsonPath('data.user.is_active', true);
    }

    #[Test]
    public function me_includes_roles(): void
    {
        $user = $this->createVerifiedUser();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.roles', ['client']);
    }

    #[Test]
    public function me_includes_multiple_roles(): void
    {
        $user = $this->createVerifiedUser();
        $user->assignRole('talent');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me');

        $response->assertStatus(200);

        $roles = $response->json('data.roles');
        $this->assertContains('client', $roles);
        $this->assertContains('talent', $roles);
        $this->assertCount(2, $roles);
    }

    #[Test]
    public function me_includes_permissions(): void
    {
        $user = $this->createVerifiedUser();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.permissions', []);
    }

    #[Test]
    public function me_includes_real_permissions_via_role(): void
    {
        Permission::create(['name' => 'view-bookings', 'guard_name' => 'api']);
        Permission::create(['name' => 'create-bookings', 'guard_name' => 'api']);

        $user = $this->createVerifiedUser();
        $user->givePermissionTo(['view-bookings', 'create-bookings']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me');

        $response->assertStatus(200);

        $permissions = $response->json('data.permissions');
        $this->assertContains('view-bookings', $permissions);
        $this->assertContains('create-bookings', $permissions);
        $this->assertCount(2, $permissions);
    }

    #[Test]
    public function me_returns_correct_phone_verified_at_format(): void
    {
        $user = $this->createVerifiedUser();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me');

        $response->assertStatus(200);

        $phoneVerifiedAt = $response->json('data.user.phone_verified_at');
        $this->assertNotNull($phoneVerifiedAt);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $phoneVerifiedAt);
    }

    // ── Error Cases ─────────────────────────────────

    #[Test]
    public function me_without_token_returns_401(): void
    {
        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(401)
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    #[Test]
    public function me_with_expired_token_returns_401(): void
    {
        $user = $this->createVerifiedUser();

        $expiredToken = $user->createToken('expired-token', expiresAt: now()->subMinute())->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $expiredToken)
            ->getJson('/api/v1/me');

        $response->assertStatus(401)
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    // ── Route Middleware ─────────────────────────────────

    #[Test]
    public function me_route_has_throttle_and_sanctum_middleware(): void
    {
        $route = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutesByName())
            ->get('api.v1.me');

        $this->assertNotNull($route);
        $this->assertContains('auth:sanctum', $route->middleware());
        $this->assertContains('throttle:auth', $route->middleware());
    }
}
