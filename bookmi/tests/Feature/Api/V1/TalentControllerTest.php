<?php

namespace Tests\Feature\Api\V1;

use App\Enums\CalendarSlotStatus;
use App\Enums\UserRole;
use App\Models\CalendarSlot;
use App\Models\Category;
use App\Models\TalentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Tests\TestCase;

class TalentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([ThrottleRequests::class, ThrottleRequestsWithRedis::class]);
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    }

    private function createVerifiedTalent(array $attributes = []): TalentProfile
    {
        return TalentProfile::factory()->verified()->create($attributes);
    }

    public function test_can_list_verified_talents(): void
    {
        $this->createVerifiedTalent();
        $this->createVerifiedTalent();
        TalentProfile::factory()->create(['is_verified' => false]);

        $response = $this->getJson('/api/v1/talents');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_unverified_talents_are_excluded(): void
    {
        TalentProfile::factory()->create(['is_verified' => false]);
        TalentProfile::factory()->create(['is_verified' => false]);

        $response = $this->getJson('/api/v1/talents');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_can_filter_by_category_id(): void
    {
        $categoryA = Category::factory()->create();
        $categoryB = Category::factory()->create();

        $this->createVerifiedTalent(['category_id' => $categoryA->id]);
        $this->createVerifiedTalent(['category_id' => $categoryA->id]);
        $this->createVerifiedTalent(['category_id' => $categoryB->id]);

        $response = $this->getJson("/api/v1/talents?category_id={$categoryA->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_by_subcategory_id(): void
    {
        $category = Category::factory()->create();
        $subA = Category::factory()->withParent($category)->create();
        $subB = Category::factory()->withParent($category)->create();

        $this->createVerifiedTalent(['category_id' => $category->id, 'subcategory_id' => $subA->id]);
        $this->createVerifiedTalent(['category_id' => $category->id, 'subcategory_id' => $subB->id]);

        $response = $this->getJson("/api/v1/talents?subcategory_id={$subA->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_by_cachet_range(): void
    {
        $this->createVerifiedTalent(['cachet_amount' => 1000000]);
        $this->createVerifiedTalent(['cachet_amount' => 5000000]);
        $this->createVerifiedTalent(['cachet_amount' => 10000000]);

        $response = $this->getJson('/api/v1/talents?min_cachet=2000000&max_cachet=8000000');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_by_city(): void
    {
        $this->createVerifiedTalent(['city' => 'Abidjan']);
        $this->createVerifiedTalent(['city' => 'Abidjan']);
        $this->createVerifiedTalent(['city' => 'Bouaké']);

        $response = $this->getJson('/api/v1/talents?city=Abidjan');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_by_min_rating(): void
    {
        $this->createVerifiedTalent(['average_rating' => 4.50]);
        $this->createVerifiedTalent(['average_rating' => 3.00]);
        $this->createVerifiedTalent(['average_rating' => 2.00]);

        $response = $this->getJson('/api/v1/talents?min_rating=4.0');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_combine_multiple_filters(): void
    {
        $category = Category::factory()->create();

        $this->createVerifiedTalent([
            'category_id' => $category->id,
            'city' => 'Abidjan',
            'cachet_amount' => 5000000,
            'average_rating' => 4.50,
        ]);

        $this->createVerifiedTalent([
            'category_id' => $category->id,
            'city' => 'Bouaké',
            'cachet_amount' => 5000000,
            'average_rating' => 4.50,
        ]);

        $this->createVerifiedTalent([
            'city' => 'Abidjan',
            'cachet_amount' => 5000000,
            'average_rating' => 4.50,
        ]);

        $response = $this->getJson(
            "/api/v1/talents?category_id={$category->id}&city=Abidjan&min_rating=4.0"
        );

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_sort_by_rating_desc(): void
    {
        $this->createVerifiedTalent(['average_rating' => 3.00, 'stage_name' => 'Low Rating']);
        $this->createVerifiedTalent(['average_rating' => 5.00, 'stage_name' => 'Top Rating']);
        $this->createVerifiedTalent(['average_rating' => 4.00, 'stage_name' => 'Mid Rating']);

        $response = $this->getJson('/api/v1/talents?sort_by=rating&sort_direction=desc');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.attributes.stage_name', 'Top Rating')
            ->assertJsonPath('data.1.attributes.stage_name', 'Mid Rating')
            ->assertJsonPath('data.2.attributes.stage_name', 'Low Rating');
    }

    public function test_can_sort_by_cachet_amount_asc(): void
    {
        $this->createVerifiedTalent(['cachet_amount' => 10000000, 'stage_name' => 'Expensive']);
        $this->createVerifiedTalent(['cachet_amount' => 1000000, 'stage_name' => 'Cheap']);
        $this->createVerifiedTalent(['cachet_amount' => 5000000, 'stage_name' => 'Medium']);

        $response = $this->getJson('/api/v1/talents?sort_by=cachet_amount&sort_direction=asc');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.attributes.stage_name', 'Cheap')
            ->assertJsonPath('data.1.attributes.stage_name', 'Medium')
            ->assertJsonPath('data.2.attributes.stage_name', 'Expensive');
    }

    public function test_default_sort_is_created_at_desc(): void
    {
        $this->createVerifiedTalent(['stage_name' => 'Oldest']);
        $this->travel(1)->minutes();
        $this->createVerifiedTalent(['stage_name' => 'Newest']);

        $response = $this->getJson('/api/v1/talents');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals('Newest', $data[0]['attributes']['stage_name']);
    }

    public function test_cursor_pagination_returns_meta(): void
    {
        $this->createVerifiedTalent();

        $response = $this->getJson('/api/v1/talents');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['next_cursor', 'prev_cursor', 'per_page', 'has_more'],
            ]);
    }

    public function test_cursor_pagination_next_page(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->createVerifiedTalent();
        }

        $firstPage = $this->getJson('/api/v1/talents?per_page=3');
        $firstPage->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.has_more', true);

        $nextCursor = $firstPage->json('meta.next_cursor');
        $this->assertNotNull($nextCursor);

        $secondPage = $this->getJson("/api/v1/talents?per_page=3&cursor={$nextCursor}");
        $secondPage->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.has_more', false);
    }

    public function test_per_page_controls_result_count(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->createVerifiedTalent();
        }

        $response = $this->getJson('/api/v1/talents?per_page=5');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 5);
    }

    public function test_accessible_without_authentication(): void
    {
        $this->createVerifiedTalent();

        $response = $this->getJson('/api/v1/talents');

        $response->assertStatus(200);
    }

    public function test_accessible_with_authentication(): void
    {
        $user = User::factory()->create();
        $this->createVerifiedTalent();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/talents');

        $response->assertStatus(200);
    }

    public function test_validation_fails_with_invalid_category_id(): void
    {
        $response = $this->getJson('/api/v1/talents?category_id=99999');

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    public function test_validation_fails_when_min_cachet_exceeds_max_cachet(): void
    {
        $response = $this->getJson('/api/v1/talents?min_cachet=10000000&max_cachet=1000000');

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    public function test_validation_fails_with_invalid_sort_by(): void
    {
        $response = $this->getJson('/api/v1/talents?sort_by=invalid_field');

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    public function test_empty_results_returns_empty_data_array(): void
    {
        $response = $this->getJson('/api/v1/talents');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.has_more', false);
    }

    public function test_response_format_matches_json_envelope(): void
    {
        $this->createVerifiedTalent();

        $response = $this->getJson('/api/v1/talents');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'type',
                        'attributes' => [
                            'stage_name',
                            'slug',
                            'city',
                            'cachet_amount',
                            'average_rating',
                            'is_verified',
                            'talent_level',
                            'category' => ['id', 'name', 'slug', 'color_hex'],
                        ],
                    ],
                ],
                'meta' => ['next_cursor', 'prev_cursor', 'per_page', 'has_more'],
            ]);

        $firstItem = $response->json('data.0');
        $this->assertEquals('talent_profile', $firstItem['type']);
        $this->assertTrue($firstItem['attributes']['is_verified']);
    }

    // ─────────────────────────────────────────────
    // Story 1.6: Géolocalisation
    // ─────────────────────────────────────────────

    public function test_can_search_by_geolocation(): void
    {
        // Cocody ~2.5km du Plateau
        $this->createVerifiedTalent(['stage_name' => 'Cocody', 'latitude' => 5.3488, 'longitude' => -3.9883]);
        // Yopougon ~10km du Plateau
        $this->createVerifiedTalent(['stage_name' => 'Yopougon', 'latitude' => 5.3390, 'longitude' => -4.0600]);
        // Bouaké ~330km
        $this->createVerifiedTalent(['stage_name' => 'Bouake', 'latitude' => 7.6939, 'longitude' => -5.0308]);

        // Rayon 20km autour du Plateau (5.3364, -3.9683)
        $response = $this->getJson('/api/v1/talents?lat=5.3364&lng=-3.9683&radius=20');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_geolocation_results_sorted_by_distance_asc(): void
    {
        // Yopougon (~10km)
        $this->createVerifiedTalent(['stage_name' => 'Yopougon', 'latitude' => 5.3390, 'longitude' => -4.0600]);
        // Cocody (~2.5km)
        $this->createVerifiedTalent(['stage_name' => 'Cocody', 'latitude' => 5.3488, 'longitude' => -3.9883]);

        $response = $this->getJson('/api/v1/talents?lat=5.3364&lng=-3.9683&radius=50');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals('Cocody', $data[0]['attributes']['stage_name']);
        $this->assertEquals('Yopougon', $data[1]['attributes']['stage_name']);
    }

    public function test_geolocation_response_includes_distance_km(): void
    {
        $this->createVerifiedTalent(['stage_name' => 'Cocody', 'latitude' => 5.3488, 'longitude' => -3.9883]);

        $response = $this->getJson('/api/v1/talents?lat=5.3364&lng=-3.9683&radius=50');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertArrayHasKey('distance_km', $data[0]['attributes']);
        $distanceKm = $data[0]['attributes']['distance_km'];
        $this->assertGreaterThan(0, $distanceKm);
        $this->assertLessThan(10, $distanceKm);
    }

    public function test_geolocation_excludes_talents_without_coordinates(): void
    {
        $this->createVerifiedTalent(['stage_name' => 'WithCoords', 'latitude' => 5.3488, 'longitude' => -3.9883]);
        $this->createVerifiedTalent(['stage_name' => 'NoCoords']); // latitude/longitude = null

        $response = $this->getJson('/api/v1/talents?lat=5.3364&lng=-3.9683&radius=50');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.attributes.stage_name', 'WithCoords');
    }

    public function test_geolocation_combined_with_category_filter(): void
    {
        $category = Category::factory()->create();
        $otherCategory = Category::factory()->create();

        $this->createVerifiedTalent([
            'stage_name' => 'Match',
            'category_id' => $category->id,
            'latitude' => 5.3488,
            'longitude' => -3.9883,
        ]);
        $this->createVerifiedTalent([
            'stage_name' => 'WrongCategory',
            'category_id' => $otherCategory->id,
            'latitude' => 5.3488,
            'longitude' => -3.9883,
        ]);

        $response = $this->getJson("/api/v1/talents?lat=5.3364&lng=-3.9683&radius=50&category_id={$category->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.attributes.stage_name', 'Match');
    }

    public function test_validation_fails_when_lat_without_lng(): void
    {
        $response = $this->getJson('/api/v1/talents?lat=5.3364&radius=20');

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    public function test_validation_fails_when_radius_without_coordinates(): void
    {
        $response = $this->getJson('/api/v1/talents?radius=20');

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    public function test_validation_fails_with_lat_out_of_range(): void
    {
        $response = $this->getJson('/api/v1/talents?lat=95&lng=-3.96&radius=20');

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    public function test_validation_fails_with_sort_by_distance_without_geolocation(): void
    {
        $response = $this->getJson('/api/v1/talents?sort_by=distance');

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    public function test_search_without_geolocation_still_works(): void
    {
        $this->createVerifiedTalent(['stage_name' => 'Classic']);
        $this->createVerifiedTalent(['stage_name' => 'Classic2']);

        $response = $this->getJson('/api/v1/talents');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'meta' => ['next_cursor', 'prev_cursor', 'per_page', 'has_more'],
            ]);
    }

    public function test_distance_km_absent_without_geolocation(): void
    {
        $this->createVerifiedTalent();

        $response = $this->getJson('/api/v1/talents');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertArrayNotHasKey('distance_km', $data[0]['attributes']);
    }

    public function test_geolocation_pagination_works(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->createVerifiedTalent([
                'latitude' => 5.3364 + ($i * 0.001),
                'longitude' => -3.9683,
            ]);
        }

        $firstPage = $this->getJson('/api/v1/talents?lat=5.3364&lng=-3.9683&radius=50&per_page=3');
        $firstPage->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.per_page', 3)
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.has_more', true)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2);

        $secondPage = $this->getJson('/api/v1/talents?lat=5.3364&lng=-3.9683&radius=50&per_page=3&page=2');
        $secondPage->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.has_more', false)
            ->assertJsonPath('meta.current_page', 2);
    }

    // ─────────────────────────────────────────────
    // Story 1.7: Profil public talent
    // ─────────────────────────────────────────────

    public function test_can_get_talent_profile_by_slug(): void
    {
        $talent = $this->createVerifiedTalent([
            'stage_name' => 'DJ Kerozen',
            'bio' => 'Artiste ivoirien célèbre',
        ]);

        $response = $this->getJson("/api/v1/talents/{$talent->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('data.type', 'talent_profile')
            ->assertJsonPath('data.attributes.stage_name', 'DJ Kerozen')
            ->assertJsonPath('data.attributes.bio', 'Artiste ivoirien célèbre');
    }

    public function test_show_returns_detailed_profile_attributes(): void
    {
        $talent = $this->createVerifiedTalent([
            'stage_name' => 'Test Talent',
            'bio' => 'Ma bio',
            'social_links' => ['instagram' => 'https://instagram.com/test'],
            'average_rating' => 4.50,
            'total_bookings' => 10,
            'profile_completion_percentage' => 60,
        ]);

        $response = $this->getJson("/api/v1/talents/{$talent->slug}");

        $response->assertStatus(200)
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
                        'average_rating',
                        'is_verified',
                        'talent_level',
                        'profile_completion_percentage',
                        'social_links',
                        'reliability_score',
                        'reviews_count',
                        'portfolio_items',
                        'service_packages',
                        'recent_reviews',
                        'created_at',
                        'category',
                    ],
                ],
                'meta' => ['similar_talents'],
            ]);

        $attributes = $response->json('data.attributes');
        $this->assertEquals(0, $attributes['reviews_count']);
        $this->assertEquals([], $attributes['portfolio_items']);
        $this->assertEquals([], $attributes['service_packages']);
        $this->assertEquals([], $attributes['recent_reviews']);
        $this->assertIsInt($attributes['reliability_score']);
        $this->assertGreaterThan(0, $attributes['reliability_score']);
    }

    public function test_show_reliability_score_formula(): void
    {
        // verified=true → +30, rating=4.50 → min(30, round(27))=27,
        // bookings=10 → min(20,10)=10, completion=60 → min(20, 12)=12 → total=79
        $talent = $this->createVerifiedTalent([
            'average_rating' => 4.50,
            'total_bookings' => 10,
            'profile_completion_percentage' => 60,
        ]);

        $response = $this->getJson("/api/v1/talents/{$talent->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('data.attributes.reliability_score', 79);
    }

    public function test_show_reliability_score_max_100(): void
    {
        // verified=true → +30, rating=5.00 → min(30, round(30))=30,
        // bookings=25 → min(20,25)=20, completion=100 → min(20, 20)=20 → total=100
        $talent = $this->createVerifiedTalent([
            'average_rating' => 5.00,
            'total_bookings' => 25,
            'profile_completion_percentage' => 100,
        ]);

        $response = $this->getJson("/api/v1/talents/{$talent->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('data.attributes.reliability_score', 100);
    }

    public function test_show_includes_similar_talents(): void
    {
        $category = Category::factory()->create();

        $talent = $this->createVerifiedTalent([
            'category_id' => $category->id,
            'city' => 'Abidjan',
        ]);

        $this->createVerifiedTalent([
            'category_id' => $category->id,
            'city' => 'Abidjan',
            'stage_name' => 'Similar One',
        ]);

        $response = $this->getJson("/api/v1/talents/{$talent->slug}");

        $response->assertStatus(200);
        $similar = $response->json('meta.similar_talents');
        $this->assertNotEmpty($similar);
    }

    public function test_show_similar_talents_same_category_and_city(): void
    {
        $category = Category::factory()->create();
        $otherCategory = Category::factory()->create();

        $talent = $this->createVerifiedTalent([
            'category_id' => $category->id,
            'city' => 'Abidjan',
        ]);

        $this->createVerifiedTalent([
            'category_id' => $category->id,
            'city' => 'Abidjan',
            'stage_name' => 'Same Cat City',
        ]);

        $this->createVerifiedTalent([
            'category_id' => $otherCategory->id,
            'city' => 'Abidjan',
            'stage_name' => 'Diff Cat',
        ]);

        $this->createVerifiedTalent([
            'category_id' => $category->id,
            'city' => 'Bouaké',
            'stage_name' => 'Diff City',
        ]);

        $response = $this->getJson("/api/v1/talents/{$talent->slug}");

        $similar = $response->json('meta.similar_talents');
        $this->assertCount(1, $similar);
        $this->assertEquals('Same Cat City', $similar[0]['attributes']['stage_name']);
    }

    public function test_show_similar_talents_excludes_current_talent(): void
    {
        $category = Category::factory()->create();

        $talent = $this->createVerifiedTalent([
            'category_id' => $category->id,
            'city' => 'Abidjan',
            'stage_name' => 'Current',
        ]);

        $response = $this->getJson("/api/v1/talents/{$talent->slug}");

        $similar = $response->json('meta.similar_talents');
        $names = array_column(array_column($similar, 'attributes'), 'stage_name');
        $this->assertNotContains('Current', $names);
    }

    public function test_show_similar_talents_max_5(): void
    {
        $category = Category::factory()->create();

        $talent = $this->createVerifiedTalent([
            'category_id' => $category->id,
            'city' => 'Abidjan',
        ]);

        for ($i = 0; $i < 7; $i++) {
            $this->createVerifiedTalent([
                'category_id' => $category->id,
                'city' => 'Abidjan',
            ]);
        }

        $response = $this->getJson("/api/v1/talents/{$talent->slug}");

        $similar = $response->json('meta.similar_talents');
        $this->assertLessThanOrEqual(5, count($similar));
    }

    public function test_show_returns_404_for_unknown_slug(): void
    {
        $response = $this->getJson('/api/v1/talents/slug-inexistant');

        $response->assertStatus(404)
            ->assertJsonPath('error.code', 'TALENT_NOT_FOUND');
    }

    public function test_show_returns_404_for_unverified_talent(): void
    {
        $talent = TalentProfile::factory()->create(['is_verified' => false]);

        $response = $this->getJson("/api/v1/talents/{$talent->slug}");

        $response->assertStatus(404)
            ->assertJsonPath('error.code', 'TALENT_NOT_FOUND');
    }

    public function test_show_accessible_without_authentication(): void
    {
        $talent = $this->createVerifiedTalent();

        $response = $this->getJson("/api/v1/talents/{$talent->slug}");

        $response->assertStatus(200);
    }

    public function test_show_response_format_matches_json_envelope(): void
    {
        $talent = $this->createVerifiedTalent();

        $response = $this->getJson("/api/v1/talents/{$talent->slug}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'type', 'attributes'],
                'meta' => ['similar_talents'],
            ]);
    }

    // ── event_date availability filter ────────────────────────────────────────

    public function test_search_without_event_date_does_not_return_is_available(): void
    {
        $this->createVerifiedTalent();

        $response = $this->getJson('/api/v1/talents');

        $response->assertOk();
        $this->assertArrayNotHasKey('is_available', $response->json('data.0.attributes'));
    }

    public function test_search_with_event_date_returns_available_when_no_blocking_slot(): void
    {
        $talent = $this->createVerifiedTalent();
        $eventDate = now()->addDays(10)->toDateString();

        // No calendar slot created → talent is available
        $response = $this->getJson("/api/v1/talents?event_date={$eventDate}");

        $response->assertOk();
        $this->assertTrue($response->json('data.0.attributes.is_available'));
    }

    public function test_search_with_event_date_returns_unavailable_when_slot_is_blocked(): void
    {
        $talent = $this->createVerifiedTalent();
        $eventDate = now()->addDays(10)->toDateString();

        CalendarSlot::factory()->create([
            'talent_profile_id' => $talent->id,
            'date'              => $eventDate,
            'status'            => CalendarSlotStatus::Blocked,
        ]);

        $response = $this->getJson("/api/v1/talents?event_date={$eventDate}");

        $response->assertOk();
        $this->assertFalse($response->json('data.0.attributes.is_available'));
    }

    public function test_search_with_event_date_returns_available_when_slot_is_available(): void
    {
        $talent = $this->createVerifiedTalent();
        $eventDate = now()->addDays(10)->toDateString();

        CalendarSlot::factory()->create([
            'talent_profile_id' => $talent->id,
            'date'              => $eventDate,
            'status'            => CalendarSlotStatus::Available,
        ]);

        $response = $this->getJson("/api/v1/talents?event_date={$eventDate}");

        $response->assertOk();
        $this->assertTrue($response->json('data.0.attributes.is_available'));
    }

    public function test_event_date_in_the_past_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/talents?event_date=2000-01-01');

        $response->assertUnprocessable();
    }

    // ── POST /talents/{talent}/notify-availability ────────────────────────────

    public function test_authenticated_user_can_register_availability_alert(): void
    {
        $talent = $this->createVerifiedTalent();
        $user = User::factory()->create();
        $user->assignRole(UserRole::CLIENT->value);
        $eventDate = now()->addDays(15)->toDateString();

        $this->actingAs($user)
            ->postJson("/api/v1/talents/{$talent->id}/notify-availability", [
                'event_date' => $eventDate,
            ])
            ->assertOk();

        $this->assertDatabaseHas('availability_alerts', [
            'user_id'           => $user->id,
            'talent_profile_id' => $talent->id,
            'event_date'        => $eventDate,
        ]);
    }

    public function test_availability_alert_is_idempotent(): void
    {
        $talent = $this->createVerifiedTalent();
        $user = User::factory()->create();
        $user->assignRole(UserRole::CLIENT->value);
        $eventDate = now()->addDays(15)->toDateString();

        $this->actingAs($user)->postJson("/api/v1/talents/{$talent->id}/notify-availability", [
            'event_date' => $eventDate,
        ])->assertOk();

        $this->actingAs($user)->postJson("/api/v1/talents/{$talent->id}/notify-availability", [
            'event_date' => $eventDate,
        ])->assertOk();

        $this->assertDatabaseCount('availability_alerts', 1);
    }

    public function test_notify_availability_requires_authentication(): void
    {
        $talent = $this->createVerifiedTalent();

        $this->postJson("/api/v1/talents/{$talent->id}/notify-availability", [
            'event_date' => now()->addDays(5)->toDateString(),
        ])->assertUnauthorized();
    }

    public function test_notify_availability_rejects_past_date(): void
    {
        $talent = $this->createVerifiedTalent();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/v1/talents/{$talent->id}/notify-availability", [
                'event_date' => '2000-01-01',
            ])
            ->assertUnprocessable();
    }
}
