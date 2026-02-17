<?php

namespace Tests\Feature\Api\V1;

use App\Models\ServicePackage;
use App\Models\TalentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServicePackageControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthenticatedTalent(array $profileOverrides = []): array
    {
        $user = User::factory()->create();
        $talent = TalentProfile::factory()->verified()->for($user)->create($profileOverrides);
        $this->actingAs($user, 'sanctum');

        return [$user, $talent];
    }

    // --- AC1: Créer un package ---

    public function test_talent_can_create_service_package(): void
    {
        [$user, $talent] = $this->createAuthenticatedTalent();

        $response = $this->postJson('/api/v1/service_packages', [
            'name' => 'Essentiel',
            'description' => 'Prestation basique 1h30',
            'cachet_amount' => 8000000,
            'duration_minutes' => 90,
            'type' => 'essentiel',
            'inclusions' => ['Sonorisation'],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'service_package')
            ->assertJsonPath('data.attributes.name', 'Essentiel')
            ->assertJsonPath('data.attributes.cachet_amount', 8000000)
            ->assertJsonPath('data.attributes.duration_minutes', 90)
            ->assertJsonPath('data.attributes.type', 'essentiel')
            ->assertJsonPath('data.attributes.inclusions', ['Sonorisation'])
            ->assertJsonPath('data.attributes.is_active', true);

        $this->assertDatabaseHas('service_packages', [
            'talent_profile_id' => $talent->id,
            'name' => 'Essentiel',
            'cachet_amount' => 8000000,
        ]);
    }

    // --- AC8: Validation ---

    public function test_create_package_validates_required_fields(): void
    {
        [$user, $talent] = $this->createAuthenticatedTalent();

        $response = $this->postJson('/api/v1/service_packages', []);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure([
                'error' => ['details' => ['errors' => ['name', 'cachet_amount', 'type']]],
            ]);
    }

    // --- AC2: Types supportés ---

    public function test_create_package_validates_type_enum(): void
    {
        [$user, $talent] = $this->createAuthenticatedTalent();

        $response = $this->postJson('/api/v1/service_packages', [
            'name' => 'Test',
            'cachet_amount' => 5000000,
            'duration_minutes' => 60,
            'type' => 'invalid_type',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure([
                'error' => ['details' => ['errors' => ['type']]],
            ]);
    }

    // --- AC2 + FR24: Micro-prestations ---

    public function test_create_micro_package_without_duration(): void
    {
        [$user, $talent] = $this->createAuthenticatedTalent();

        $response = $this->postJson('/api/v1/service_packages', [
            'name' => 'Vidéo personnalisée',
            'cachet_amount' => 5000000,
            'type' => 'micro',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.attributes.type', 'micro')
            ->assertJsonPath('data.attributes.duration_minutes', null);
    }

    // --- Auth ---

    public function test_create_package_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/service_packages', [
            'name' => 'Test',
            'cachet_amount' => 5000000,
            'duration_minutes' => 60,
            'type' => 'essentiel',
        ]);

        $response->assertStatus(401);
    }

    // --- Talent profile required ---

    public function test_create_package_requires_talent_profile(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/v1/service_packages', [
            'name' => 'Test',
            'cachet_amount' => 5000000,
            'duration_minutes' => 60,
            'type' => 'essentiel',
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('error.code', 'TALENT_PROFILE_NOT_FOUND');
    }

    // --- AC5: Lister ses packages ---

    public function test_talent_can_list_own_packages(): void
    {
        [$user, $talent] = $this->createAuthenticatedTalent();

        ServicePackage::factory()->count(3)->for($talent, 'talentProfile')->create();

        // Autre talent avec des packages (ne doivent pas apparaître)
        $otherTalent = TalentProfile::factory()->verified()->create();
        ServicePackage::factory()->count(2)->for($otherTalent, 'talentProfile')->create();

        $response = $this->getJson('/api/v1/service_packages');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_list_packages_ordered_by_sort_order(): void
    {
        [$user, $talent] = $this->createAuthenticatedTalent();

        ServicePackage::factory()->for($talent, 'talentProfile')->create([
            'name' => 'Premium',
            'sort_order' => 2,
        ]);
        ServicePackage::factory()->for($talent, 'talentProfile')->create([
            'name' => 'Essentiel',
            'sort_order' => 0,
        ]);
        ServicePackage::factory()->for($talent, 'talentProfile')->create([
            'name' => 'Standard',
            'sort_order' => 1,
        ]);

        $response = $this->getJson('/api/v1/service_packages');

        $response->assertStatus(200);
        $names = collect($response->json('data'))->pluck('attributes.name')->toArray();
        $this->assertEquals(['Essentiel', 'Standard', 'Premium'], $names);
    }

    // --- AC3: Modifier un package ---

    public function test_talent_can_update_own_package(): void
    {
        [$user, $talent] = $this->createAuthenticatedTalent();
        $package = ServicePackage::factory()->for($talent, 'talentProfile')->create([
            'name' => 'Ancien nom',
        ]);

        $response = $this->putJson("/api/v1/service_packages/{$package->id}", [
            'name' => 'Nouveau nom',
            'cachet_amount' => 15000000,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.attributes.name', 'Nouveau nom')
            ->assertJsonPath('data.attributes.cachet_amount', 15000000);
    }

    public function test_talent_cannot_update_other_talent_package(): void
    {
        [$user, $talent] = $this->createAuthenticatedTalent();

        $otherUser = User::factory()->create();
        $otherTalent = TalentProfile::factory()->verified()->for($otherUser)->create();
        $package = ServicePackage::factory()->for($otherTalent, 'talentProfile')->create();

        $response = $this->putJson("/api/v1/service_packages/{$package->id}", [
            'name' => 'Hacked',
        ]);

        $response->assertStatus(403);
    }

    // --- AC4: Supprimer un package ---

    public function test_talent_can_delete_own_package(): void
    {
        [$user, $talent] = $this->createAuthenticatedTalent();
        $package = ServicePackage::factory()->for($talent, 'talentProfile')->create();

        $response = $this->deleteJson("/api/v1/service_packages/{$package->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('service_packages', ['id' => $package->id]);
    }

    public function test_talent_cannot_delete_other_talent_package(): void
    {
        [$user, $talent] = $this->createAuthenticatedTalent();

        $otherUser = User::factory()->create();
        $otherTalent = TalentProfile::factory()->verified()->for($otherUser)->create();
        $package = ServicePackage::factory()->for($otherTalent, 'talentProfile')->create();

        $response = $this->deleteJson("/api/v1/service_packages/{$package->id}");

        $response->assertStatus(403);
    }

    // --- AC6: Packages sur le profil public ---

    public function test_public_profile_includes_active_packages(): void
    {
        $talent = TalentProfile::factory()->verified()->create();
        ServicePackage::factory()->for($talent, 'talentProfile')->create([
            'name' => 'Essentiel',
            'is_active' => true,
        ]);
        ServicePackage::factory()->for($talent, 'talentProfile')->create([
            'name' => 'Premium',
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/v1/talents/{$talent->slug}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.attributes.service_packages');
    }

    public function test_public_profile_excludes_inactive_packages(): void
    {
        $talent = TalentProfile::factory()->verified()->create();
        ServicePackage::factory()->for($talent, 'talentProfile')->create([
            'name' => 'Active',
            'is_active' => true,
        ]);
        ServicePackage::factory()->inactive()->for($talent, 'talentProfile')->create([
            'name' => 'Inactive',
        ]);

        $response = $this->getJson("/api/v1/talents/{$talent->slug}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.attributes.service_packages');
    }

    public function test_public_profile_excludes_deleted_packages(): void
    {
        $talent = TalentProfile::factory()->verified()->create();
        ServicePackage::factory()->for($talent, 'talentProfile')->create([
            'name' => 'Active',
            'is_active' => true,
        ]);
        ServicePackage::factory()->for($talent, 'talentProfile')->create([
            'name' => 'Deleted',
            'is_active' => true,
            'deleted_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/talents/{$talent->slug}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.attributes.service_packages');
    }

    // --- AC7: Profile completion ---

    public function test_create_package_updates_profile_completion(): void
    {
        [$user, $talent] = $this->createAuthenticatedTalent([
            'bio' => 'Bio test',
            'is_verified' => true,
            'profile_completion_percentage' => 40,
        ]);

        $this->postJson('/api/v1/service_packages', [
            'name' => 'Essentiel',
            'cachet_amount' => 8000000,
            'duration_minutes' => 90,
            'type' => 'essentiel',
        ]);

        $talent->refresh();
        // bio=20 + verified=20 + packages=20 = 60
        $this->assertEquals(60, $talent->profile_completion_percentage);
    }

    public function test_delete_last_package_updates_profile_completion(): void
    {
        [$user, $talent] = $this->createAuthenticatedTalent([
            'bio' => 'Bio test',
            'is_verified' => true,
            'profile_completion_percentage' => 60,
        ]);

        $package = ServicePackage::factory()->for($talent, 'talentProfile')->create();

        $this->deleteJson("/api/v1/service_packages/{$package->id}");

        $talent->refresh();
        // bio=20 + verified=20 + packages=0 = 40
        $this->assertEquals(40, $talent->profile_completion_percentage);
    }

    // --- Données ---

    public function test_cachet_amount_stored_as_centimes(): void
    {
        [$user, $talent] = $this->createAuthenticatedTalent();

        $response = $this->postJson('/api/v1/service_packages', [
            'name' => 'Test',
            'cachet_amount' => 15000000,
            'duration_minutes' => 120,
            'type' => 'standard',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('service_packages', [
            'cachet_amount' => 15000000,
        ]);
        $this->assertIsInt($response->json('data.attributes.cachet_amount'));
    }

    public function test_inclusions_stored_as_json_array(): void
    {
        [$user, $talent] = $this->createAuthenticatedTalent();

        $inclusions = ['Sonorisation', '2 danseurs', 'Éclairage'];

        $response = $this->postJson('/api/v1/service_packages', [
            'name' => 'Premium',
            'cachet_amount' => 20000000,
            'duration_minutes' => 180,
            'type' => 'premium',
            'inclusions' => $inclusions,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.attributes.inclusions', $inclusions);
    }

    // --- Review Fixes: H1 — is_active toggle recalculates completion ---

    public function test_deactivate_last_package_updates_profile_completion(): void
    {
        [$user, $talent] = $this->createAuthenticatedTalent([
            'bio' => 'Bio test',
            'is_verified' => true,
            'profile_completion_percentage' => 60,
        ]);

        $package = ServicePackage::factory()->for($talent, 'talentProfile')->create([
            'is_active' => true,
        ]);

        $this->putJson("/api/v1/service_packages/{$package->id}", [
            'is_active' => false,
        ]);

        $talent->refresh();
        // bio=20 + verified=20 + packages=0 = 40
        $this->assertEquals(40, $talent->profile_completion_percentage);
    }

    // --- Review Fixes: H2 — type change micro→essentiel requires duration ---

    public function test_update_type_micro_to_essentiel_without_duration_fails(): void
    {
        [$user, $talent] = $this->createAuthenticatedTalent();
        $package = ServicePackage::factory()->micro()->for($talent, 'talentProfile')->create();

        $response = $this->putJson("/api/v1/service_packages/{$package->id}", [
            'type' => 'essentiel',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    public function test_update_type_micro_to_essentiel_with_duration_succeeds(): void
    {
        [$user, $talent] = $this->createAuthenticatedTalent();
        $package = ServicePackage::factory()->micro()->for($talent, 'talentProfile')->create();

        $response = $this->putJson("/api/v1/service_packages/{$package->id}", [
            'type' => 'essentiel',
            'duration_minutes' => 90,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.attributes.type', 'essentiel')
            ->assertJsonPath('data.attributes.duration_minutes', 90);
    }

    // --- Review Fixes: M2 — Boundary validation tests ---

    public function test_create_package_validates_name_max_length(): void
    {
        [$user, $talent] = $this->createAuthenticatedTalent();

        $response = $this->postJson('/api/v1/service_packages', [
            'name' => str_repeat('A', 151),
            'cachet_amount' => 5000000,
            'duration_minutes' => 60,
            'type' => 'essentiel',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure([
                'error' => ['details' => ['errors' => ['name']]],
            ]);
    }

    public function test_create_package_validates_cachet_amount_minimum(): void
    {
        [$user, $talent] = $this->createAuthenticatedTalent();

        $response = $this->postJson('/api/v1/service_packages', [
            'name' => 'Test',
            'cachet_amount' => 500,
            'duration_minutes' => 60,
            'type' => 'essentiel',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure([
                'error' => ['details' => ['errors' => ['cachet_amount']]],
            ]);
    }

    public function test_create_package_validates_duration_minutes_positive(): void
    {
        [$user, $talent] = $this->createAuthenticatedTalent();

        $response = $this->postJson('/api/v1/service_packages', [
            'name' => 'Test',
            'cachet_amount' => 5000000,
            'duration_minutes' => 0,
            'type' => 'essentiel',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure([
                'error' => ['details' => ['errors' => ['duration_minutes']]],
            ]);
    }

    public function test_create_package_validates_cachet_amount_maximum(): void
    {
        [$user, $talent] = $this->createAuthenticatedTalent();

        $response = $this->postJson('/api/v1/service_packages', [
            'name' => 'Test',
            'cachet_amount' => 3000000000,
            'duration_minutes' => 60,
            'type' => 'essentiel',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure([
                'error' => ['details' => ['errors' => ['cachet_amount']]],
            ]);
    }
}
