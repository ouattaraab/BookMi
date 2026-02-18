<?php

namespace Tests\Feature\Api\V1;

use App\Models\TalentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthenticatedUser(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        return $user;
    }

    private function createVerifiedTalent(array $overrides = []): TalentProfile
    {
        return TalentProfile::factory()->verified()->create($overrides);
    }

    // --- AC1: Ajouter un talent en favori ---

    public function test_client_can_add_talent_to_favorites(): void
    {
        $user = $this->createAuthenticatedUser();
        $talent = $this->createVerifiedTalent();

        $response = $this->postJson("/api/v1/talents/{$talent->id}/favorite");

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'favorite')
            ->assertJsonPath('data.attributes.talent.id', $talent->id)
            ->assertJsonPath('data.attributes.talent.type', 'talent_profile')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'type',
                    'attributes' => [
                        'talent',
                        'favorited_at',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('user_favorites', [
            'user_id' => $user->id,
            'talent_profile_id' => $talent->id,
        ]);
    }

    public function test_add_favorite_returns_409_if_already_favorited(): void
    {
        $user = $this->createAuthenticatedUser();
        $talent = $this->createVerifiedTalent();

        $user->favorites()->attach($talent->id);

        $response = $this->postJson("/api/v1/talents/{$talent->id}/favorite");

        $response->assertStatus(409)
            ->assertJsonPath('error.code', 'ALREADY_FAVORITED');
    }

    // --- AC6: Talent inexistant ---

    public function test_add_favorite_returns_404_for_nonexistent_talent(): void
    {
        $this->createAuthenticatedUser();

        $response = $this->postJson('/api/v1/talents/99999/favorite');

        $response->assertStatus(404)
            ->assertJsonPath('error.code', 'TALENT_NOT_FOUND');
    }

    // --- AC2: Retirer un talent des favoris ---

    public function test_client_can_remove_talent_from_favorites(): void
    {
        $user = $this->createAuthenticatedUser();
        $talent = $this->createVerifiedTalent();

        $user->favorites()->attach($talent->id);

        $response = $this->deleteJson("/api/v1/talents/{$talent->id}/favorite");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('user_favorites', [
            'user_id' => $user->id,
            'talent_profile_id' => $talent->id,
        ]);
    }

    public function test_remove_favorite_returns_404_if_not_favorited(): void
    {
        $this->createAuthenticatedUser();
        $talent = $this->createVerifiedTalent();

        $response = $this->deleteJson("/api/v1/talents/{$talent->id}/favorite");

        $response->assertStatus(404)
            ->assertJsonPath('error.code', 'FAVORITE_NOT_FOUND');
    }

    // --- AC3: Lister ses favoris avec pagination ---

    public function test_client_can_list_favorites_with_pagination(): void
    {
        $user = $this->createAuthenticatedUser();
        $talents = TalentProfile::factory()->verified()->count(3)->create();

        foreach ($talents as $talent) {
            $user->favorites()->attach($talent->id);
        }

        $response = $this->getJson('/api/v1/me/favorites?per_page=2');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'attributes' => [
                            'talent' => [
                                'id',
                                'type',
                                'attributes' => [
                                    'stage_name',
                                    'slug',
                                    'city',
                                    'cachet_amount',
                                    'average_rating',
                                    'is_verified',
                                    'category',
                                ],
                            ],
                            'favorited_at',
                        ],
                    ],
                ],
                'meta' => [
                    'next_cursor',
                    'prev_cursor',
                    'per_page',
                    'has_more',
                ],
            ])
            ->assertJsonPath('meta.has_more', true)
            ->assertJsonPath('meta.per_page', 2);
    }

    public function test_favorites_list_excludes_unverified_talents(): void
    {
        $user = $this->createAuthenticatedUser();
        $verified = $this->createVerifiedTalent();
        $unverified = TalentProfile::factory()->create(['is_verified' => false]);

        $user->favorites()->attach($verified->id);
        $user->favorites()->attach($unverified->id);

        $response = $this->getJson('/api/v1/me/favorites');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $returnedId = $response->json('data.0.attributes.talent.id');
        $this->assertEquals($verified->id, $returnedId);
    }

    public function test_favorites_list_excludes_deleted_talents(): void
    {
        $user = $this->createAuthenticatedUser();
        $active = $this->createVerifiedTalent();
        $deleted = $this->createVerifiedTalent();

        $user->favorites()->attach($active->id);
        $user->favorites()->attach($deleted->id);

        $deleted->delete(); // soft delete

        $response = $this->getJson('/api/v1/me/favorites');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $returnedId = $response->json('data.0.attributes.talent.id');
        $this->assertEquals($active->id, $returnedId);
    }

    // --- AC4: Vérifier si un talent est en favori ---

    public function test_client_can_check_favorite_status(): void
    {
        $user = $this->createAuthenticatedUser();
        $talent = $this->createVerifiedTalent();

        // Not favorited
        $response = $this->getJson("/api/v1/talents/{$talent->id}/favorite");
        $response->assertStatus(200)
            ->assertJsonPath('data.is_favorite', false);

        // Favorited
        $user->favorites()->attach($talent->id);

        $response = $this->getJson("/api/v1/talents/{$talent->id}/favorite");
        $response->assertStatus(200)
            ->assertJsonPath('data.is_favorite', true);
    }

    // --- AC5: Authentification requise ---

    public function test_favorites_require_authentication(): void
    {
        $talent = $this->createVerifiedTalent();

        $this->getJson('/api/v1/me/favorites')->assertStatus(401);
        $this->postJson("/api/v1/talents/{$talent->id}/favorite")->assertStatus(401);
        $this->deleteJson("/api/v1/talents/{$talent->id}/favorite")->assertStatus(401);
        $this->getJson("/api/v1/talents/{$talent->id}/favorite")->assertStatus(401);
    }

    // --- AC3: Ordre par date d'ajout ---

    public function test_favorites_ordered_by_most_recent(): void
    {
        $user = $this->createAuthenticatedUser();
        $talent1 = $this->createVerifiedTalent(['stage_name' => 'Premier']);
        $talent2 = $this->createVerifiedTalent(['stage_name' => 'Deuxième']);

        // Add first, then second (with slight delay via pivot timestamps)
        $user->favorites()->attach($talent1->id, ['created_at' => now()->subMinute()]);
        $user->favorites()->attach($talent2->id, ['created_at' => now()]);

        $response = $this->getJson('/api/v1/me/favorites');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Most recent first
        $this->assertEquals($talent2->id, $data[0]['attributes']['talent']['id']);
        $this->assertEquals($talent1->id, $data[1]['attributes']['talent']['id']);
    }

    // --- AC3: Données talent incluses ---

    public function test_favorite_includes_talent_summary_data(): void
    {
        $user = $this->createAuthenticatedUser();
        $talent = $this->createVerifiedTalent([
            'stage_name' => 'DJ Test',
            'city' => 'Abidjan',
            'cachet_amount' => 5000000,
        ]);

        $user->favorites()->attach($talent->id);

        $response = $this->getJson('/api/v1/me/favorites');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.type', 'favorite')
            ->assertJsonPath('data.0.attributes.talent.type', 'talent_profile')
            ->assertJsonPath('data.0.attributes.talent.attributes.stage_name', 'DJ Test')
            ->assertJsonPath('data.0.attributes.talent.attributes.city', 'Abidjan')
            ->assertJsonPath('data.0.attributes.talent.attributes.cachet_amount', 5000000)
            ->assertJsonPath('data.0.attributes.talent.attributes.is_verified', true);

        // favorited_at is present
        $this->assertNotNull($response->json('data.0.attributes.favorited_at'));
    }

    // --- Edge case: talent non vérifié ne peut pas être ajouté ---

    public function test_add_favorite_returns_404_for_unverified_talent(): void
    {
        $this->createAuthenticatedUser();
        $unverified = TalentProfile::factory()->create(['is_verified' => false]);

        $response = $this->postJson("/api/v1/talents/{$unverified->id}/favorite");

        $response->assertStatus(404)
            ->assertJsonPath('error.code', 'TALENT_NOT_FOUND');
    }
}
