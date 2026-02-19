<?php

namespace Tests\Feature\Api\V1;

use App\Models\TalentProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ManagerAssignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function createTalentWithProfile(): array
    {
        $user = User::factory()->create();
        $user->assignRole('talent');
        $talent = TalentProfile::factory()->create(['user_id' => $user->id]);

        return [$user, $talent];
    }

    private function createManager(): User
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        return $manager;
    }

    // ─────────────────────────────────────────────
    // Story 7.1 — Assign manager
    // ─────────────────────────────────────────────

    #[Test]
    public function talent_can_assign_a_manager_by_email(): void
    {
        [$talentUser, $talent] = $this->createTalentWithProfile();
        $manager = $this->createManager();

        $this->actingAs($talentUser, 'sanctum');

        $response = $this->postJson('/api/v1/talent_profiles/me/manager', [
            'manager_email' => $manager->email,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.message', 'Manager assigné avec succès.');

        $this->assertDatabaseHas('talent_manager', [
            'talent_profile_id' => $talent->id,
            'manager_id' => $manager->id,
        ]);
    }

    #[Test]
    public function assign_manager_fails_if_email_not_found(): void
    {
        [$talentUser] = $this->createTalentWithProfile();

        $this->actingAs($talentUser, 'sanctum');

        $response = $this->postJson('/api/v1/talent_profiles/me/manager', [
            'manager_email' => 'nobody@example.com',
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('error.code', 'MANAGER_NOT_FOUND');
    }

    #[Test]
    public function assign_manager_fails_if_user_lacks_manager_role(): void
    {
        [$talentUser] = $this->createTalentWithProfile();
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($talentUser, 'sanctum');

        $response = $this->postJson('/api/v1/talent_profiles/me/manager', [
            'manager_email' => $client->email,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'MANAGER_ROLE_REQUIRED');
    }

    #[Test]
    public function assign_manager_fails_if_already_assigned(): void
    {
        [$talentUser, $talent] = $this->createTalentWithProfile();
        $manager = $this->createManager();

        $talent->managers()->attach($manager->id, ['assigned_at' => now()]);

        $this->actingAs($talentUser, 'sanctum');

        $response = $this->postJson('/api/v1/talent_profiles/me/manager', [
            'manager_email' => $manager->email,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'MANAGER_ALREADY_ASSIGNED');
    }

    // ─────────────────────────────────────────────
    // Story 7.1 — Unassign manager
    // ─────────────────────────────────────────────

    #[Test]
    public function talent_can_unassign_an_assigned_manager(): void
    {
        [$talentUser, $talent] = $this->createTalentWithProfile();
        $manager = $this->createManager();

        $talent->managers()->attach($manager->id, ['assigned_at' => now()]);

        $this->actingAs($talentUser, 'sanctum');

        $response = $this->deleteJson('/api/v1/talent_profiles/me/manager', [
            'manager_email' => $manager->email,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.message', 'Manager désassigné avec succès.');

        $this->assertDatabaseMissing('talent_manager', [
            'talent_profile_id' => $talent->id,
            'manager_id' => $manager->id,
        ]);
    }

    #[Test]
    public function unassign_fails_if_manager_not_assigned(): void
    {
        [$talentUser] = $this->createTalentWithProfile();
        $manager = $this->createManager();

        $this->actingAs($talentUser, 'sanctum');

        $response = $this->deleteJson('/api/v1/talent_profiles/me/manager', [
            'manager_email' => $manager->email,
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('error.code', 'MANAGER_NOT_ASSIGNED');
    }

    // ─────────────────────────────────────────────
    // Story 7.2 — Manager interface
    // ─────────────────────────────────────────────

    #[Test]
    public function manager_can_list_their_talents(): void
    {
        $manager = $this->createManager();
        $talent1 = TalentProfile::factory()->create();
        $talent2 = TalentProfile::factory()->create();

        $manager->managedTalents()->attach($talent1->id, ['assigned_at' => now()]);
        $manager->managedTalents()->attach($talent2->id, ['assigned_at' => now()]);

        $this->actingAs($manager, 'sanctum');

        $response = $this->getJson('/api/v1/manager/talents');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.talents');
    }

    #[Test]
    public function manager_can_view_talent_stats(): void
    {
        $manager = $this->createManager();
        $talent = TalentProfile::factory()->create();

        $manager->managedTalents()->attach($talent->id, ['assigned_at' => now()]);

        $this->actingAs($manager, 'sanctum');

        $response = $this->getJson("/api/v1/manager/talents/{$talent->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.talent_profile_id', $talent->id)
            ->assertJsonStructure(['data' => [
                'stage_name', 'talent_level', 'average_rating',
                'total_bookings', 'pending_bookings', 'confirmed_bookings',
            ]]);
    }

    #[Test]
    public function manager_cannot_view_stats_of_unassigned_talent(): void
    {
        $manager = $this->createManager();
        $talent = TalentProfile::factory()->create();

        $this->actingAs($manager, 'sanctum');

        $response = $this->getJson("/api/v1/manager/talents/{$talent->id}");

        $response->assertStatus(403)
            ->assertJsonPath('error.code', 'MANAGER_UNAUTHORIZED');
    }

    #[Test]
    public function non_manager_cannot_access_manager_routes(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client, 'sanctum');

        $response = $this->getJson('/api/v1/manager/talents');

        $response->assertStatus(403);
    }
}
