<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\TalentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TalentProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->category = Category::factory()->create(['name' => 'DJ']);
    }

    public function test_authenticated_user_can_create_talent_profile(): void
    {
        $data = [
            'stage_name'   => 'DJ Kerozen',
            'category_ids' => [$this->category->id],
            'city'         => 'Abidjan',
            'cachet_amount' => 15000000,
            'bio'          => 'Meilleur DJ de Côte d\'Ivoire',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/talent_profiles', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'type',
                    'attributes' => [
                        'stage_name',
                        'slug',
                        'bio',
                        'city',
                        'cachet_amount',
                        'is_verified',
                        'talent_level',
                        'profile_completion_percentage',
                        'category' => ['id', 'name', 'slug', 'color_hex'],
                    ],
                ],
            ]);

        $this->assertDatabaseHas('talent_profiles', [
            'user_id' => $this->user->id,
            'stage_name' => 'DJ Kerozen',
            'cachet_amount' => 15000000,
        ]);

        $response->assertJsonPath('data.type', 'talent_profile');
        $response->assertJsonPath('data.attributes.slug', 'dj-kerozen');
        $response->assertJsonPath('data.attributes.profile_completion_percentage', 20);
    }

    public function test_cannot_create_profile_without_authentication(): void
    {
        $response = $this->postJson('/api/v1/talent_profiles', [
            'stage_name' => 'DJ Test',
            'category_id' => $this->category->id,
            'city' => 'Abidjan',
            'cachet_amount' => 5000,
        ]);

        $response->assertStatus(401);
    }

    public function test_cannot_create_duplicate_profile(): void
    {
        TalentProfile::factory()->create([
            'user_id'     => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/talent_profiles', [
                'stage_name'   => 'Another Name',
                'category_ids' => [$this->category->id],
                'city'         => 'Bouaké',
                'cachet_amount' => 5000,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'TALENT_ALREADY_HAS_PROFILE');
    }

    public function test_validation_fails_with_missing_required_fields(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/talent_profiles', []);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure([
                'error' => [
                    'code',
                    'message',
                    'status',
                    'details' => [
                        'errors' => ['stage_name', 'category_ids', 'city', 'cachet_amount'],
                    ],
                ],
            ]);
    }

    public function test_validation_fails_with_invalid_cachet_amount(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/talent_profiles', [
                'stage_name'   => 'DJ Test',
                'category_ids' => [$this->category->id],
                'city'         => 'Abidjan',
                'cachet_amount' => 500,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure([
                'error' => ['details' => ['errors' => ['cachet_amount']]],
            ]);
    }

    public function test_validation_fails_with_duplicate_stage_name(): void
    {
        TalentProfile::factory()->create([
            'stage_name' => 'DJ Kerozen',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/talent_profiles', [
                'stage_name'   => 'DJ Kerozen',
                'category_ids' => [$this->category->id],
                'city'         => 'Abidjan',
                'cachet_amount' => 5000,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure([
                'error' => ['details' => ['errors' => ['stage_name']]],
            ]);
    }

    public function test_authenticated_user_can_get_own_profile(): void
    {
        $profile = TalentProfile::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'stage_name' => 'DJ Test',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/talent_profiles/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $profile->id)
            ->assertJsonPath('data.attributes.stage_name', 'DJ Test');
    }

    public function test_returns_404_when_no_profile_exists(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/talent_profiles/me');

        $response->assertStatus(404)
            ->assertJsonPath('error.code', 'TALENT_PROFILE_NOT_FOUND');
    }

    public function test_owner_can_update_own_profile(): void
    {
        $profile = TalentProfile::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'stage_name' => 'DJ Original',
        ]);

        $originalSlug = $profile->slug;

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/v1/talent_profiles/{$profile->id}", [
                'bio' => 'Nouvelle bio mise à jour',
                'city' => 'Yamoussoukro',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.attributes.bio', 'Nouvelle bio mise à jour')
            ->assertJsonPath('data.attributes.city', 'Yamoussoukro')
            ->assertJsonPath('data.attributes.slug', $originalSlug);
    }

    public function test_cannot_update_other_users_profile(): void
    {
        $otherUser = User::factory()->create();
        $profile = TalentProfile::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/v1/talent_profiles/{$profile->id}", [
                'bio' => 'Tentative de modification',
            ]);

        $response->assertStatus(403);
    }

    public function test_owner_can_delete_own_profile(): void
    {
        $profile = TalentProfile::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/talent_profiles/{$profile->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('talent_profiles', ['id' => $profile->id]);
    }

    public function test_cannot_delete_other_users_profile(): void
    {
        $otherUser = User::factory()->create();
        $profile = TalentProfile::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/talent_profiles/{$profile->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('talent_profiles', ['id' => $profile->id, 'deleted_at' => null]);
    }

    public function test_talent_can_select_multiple_categories(): void
    {
        $cat2 = Category::factory()->create(['name' => 'Chanteur']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/talent_profiles', [
                'stage_name'   => 'DJ Multi',
                'category_ids' => [$this->category->id, $cat2->id],
                'city'         => 'Abidjan',
                'cachet_amount' => 10000,
            ]);

        $response->assertStatus(201);

        $profileId = $response->json('data.id');

        // Both categories must exist in the pivot table
        $this->assertDatabaseHas('talent_profile_categories', [
            'talent_profile_id' => $profileId,
            'category_id'       => $this->category->id,
        ]);
        $this->assertDatabaseHas('talent_profile_categories', [
            'talent_profile_id' => $profileId,
            'category_id'       => $cat2->id,
        ]);

        // Primary category_id must be the first one
        $this->assertDatabaseHas('talent_profiles', [
            'id'          => $profileId,
            'category_id' => $this->category->id,
        ]);

        // API response must include categories array
        $categories = $response->json('data.attributes.categories');
        $this->assertCount(2, $categories);
    }

    public function test_talent_appears_in_filter_for_all_selected_categories(): void
    {
        $cat2 = Category::factory()->create(['name' => 'Danseur']);

        // Create a talent with 2 categories
        $profile = TalentProfile::factory()->verified()->create([
            'user_id'     => $this->user->id,
            'category_id' => $this->category->id,
        ]);
        // Add the second category to the pivot
        $profile->categories()->syncWithoutDetaching([$cat2->id]);

        // Filter by first category
        $r1 = $this->getJson('/api/v1/talents?category_id=' . $this->category->id);
        $r1->assertStatus(200);
        $ids1 = collect($r1->json('data'))->pluck('id')->toArray();
        $this->assertContains($profile->id, $ids1);

        // Filter by second category
        $r2 = $this->getJson('/api/v1/talents?category_id=' . $cat2->id);
        $r2->assertStatus(200);
        $ids2 = collect($r2->json('data'))->pluck('id')->toArray();
        $this->assertContains($profile->id, $ids2);
    }

    public function test_social_links_stored_as_json(): void
    {
        $socialLinks = [
            'instagram' => 'https://instagram.com/djkerozen',
            'youtube' => 'https://youtube.com/@djkerozen',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/talent_profiles', [
                'stage_name'   => 'DJ Social',
                'category_ids' => [$this->category->id],
                'city'         => 'Abidjan',
                'cachet_amount' => 5000,
                'social_links' => $socialLinks,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.attributes.social_links.instagram', 'https://instagram.com/djkerozen')
            ->assertJsonPath('data.attributes.social_links.youtube', 'https://youtube.com/@djkerozen');
    }
}
