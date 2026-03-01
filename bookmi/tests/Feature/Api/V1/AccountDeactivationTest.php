<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Tests\TestCase;

class AccountDeactivationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([ThrottleRequests::class, ThrottleRequestsWithRedis::class]);
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    }

    public function test_authenticated_user_can_deactivate_account(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->postJson('/api/v1/me/deactivate')
            ->assertOk()
            ->assertJsonPath('data.message', 'Votre compte a été désactivé.');

        $this->assertDatabaseHas('users', [
            'id'        => $user->id,
            'is_active' => false,
        ]);
    }

    public function test_deactivation_revokes_all_tokens(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        // Create a Sanctum token
        $user->createToken('test-token');
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->actingAs($user)
            ->postJson('/api/v1/me/deactivate')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_deactivation_requires_authentication(): void
    {
        $this->postJson('/api/v1/me/deactivate')
            ->assertUnauthorized();
    }
}
