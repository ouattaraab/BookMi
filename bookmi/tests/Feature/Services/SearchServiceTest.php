<?php

namespace Tests\Feature\Services;

use App\Models\Category;
use App\Models\TalentProfile;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchServiceTest extends TestCase
{
    use RefreshDatabase;

    private SearchService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SearchService::class);
    }

    public function test_search_returns_only_verified_talents(): void
    {
        TalentProfile::factory()->verified()->create();
        TalentProfile::factory()->verified()->create();
        TalentProfile::factory()->create(['is_verified' => false]);

        $result = $this->service->searchTalents([]);

        $this->assertCount(2, $result->items());
    }

    public function test_search_maps_rating_to_average_rating_column(): void
    {
        TalentProfile::factory()->verified()->create(['average_rating' => 5.00, 'stage_name' => 'Best']);
        TalentProfile::factory()->verified()->create(['average_rating' => 3.00, 'stage_name' => 'Worst']);

        $result = $this->service->searchTalents([], sortBy: 'rating', sortDirection: 'desc');

        $items = $result->items();
        $this->assertEquals('Best', $items[0]->stage_name);
        $this->assertEquals('Worst', $items[1]->stage_name);
    }

    public function test_search_applies_default_sort_created_at_desc(): void
    {
        TalentProfile::factory()->verified()->create(['stage_name' => 'Older']);
        $this->travel(1)->minutes();
        TalentProfile::factory()->verified()->create(['stage_name' => 'Newer']);

        $result = $this->service->searchTalents([]);

        $items = $result->items();
        $this->assertEquals('Newer', $items[0]->stage_name);
    }

    public function test_search_filters_by_category(): void
    {
        $category = Category::factory()->create();
        $otherCategory = Category::factory()->create();

        TalentProfile::factory()->verified()->create(['category_id' => $category->id]);
        TalentProfile::factory()->verified()->create(['category_id' => $otherCategory->id]);

        $result = $this->service->searchTalents(['category_id' => $category->id]);

        $this->assertCount(1, $result->items());
    }

    public function test_search_applies_cachet_range_filter(): void
    {
        TalentProfile::factory()->verified()->create(['cachet_amount' => 1000000]);
        TalentProfile::factory()->verified()->create(['cachet_amount' => 5000000]);
        TalentProfile::factory()->verified()->create(['cachet_amount' => 10000000]);

        $result = $this->service->searchTalents([
            'min_cachet' => 2000000,
            'max_cachet' => 8000000,
        ]);

        $this->assertCount(1, $result->items());
    }

    public function test_search_default_per_page_is_20(): void
    {
        $result = $this->service->searchTalents([]);

        $this->assertEquals(20, $result->perPage());
    }

    // ─────────────────────────────────────────────
    // Story 1.6: Géolocalisation
    // ─────────────────────────────────────────────

    public function test_search_with_geo_params_filters_by_radius(): void
    {
        // Within 20km of reference point
        TalentProfile::factory()->verified()->create([
            'latitude' => 5.3488,
            'longitude' => -3.9883,
        ]);
        // Outside radius (~330km away)
        TalentProfile::factory()->verified()->create([
            'latitude' => 7.6939,
            'longitude' => -5.0308,
        ]);

        $result = $this->service->searchTalents([
            'lat' => '5.3364',
            'lng' => '-3.9683',
            'radius' => '20',
        ]);

        $this->assertCount(1, $result->items());
    }

    public function test_search_with_geo_defaults_sort_to_distance_asc(): void
    {
        // Farther
        TalentProfile::factory()->verified()->create([
            'stage_name' => 'Far',
            'latitude' => 5.3390,
            'longitude' => -4.0600,
        ]);
        // Closer
        TalentProfile::factory()->verified()->create([
            'stage_name' => 'Close',
            'latitude' => 5.3488,
            'longitude' => -3.9883,
        ]);

        $result = $this->service->searchTalents([
            'lat' => '5.3364',
            'lng' => '-3.9683',
            'radius' => '50',
        ]);

        $items = $result->items();
        $this->assertEquals('Close', $items[0]->stage_name);
        $this->assertEquals('Far', $items[1]->stage_name);
    }

    public function test_search_without_geo_preserves_default_sort(): void
    {
        TalentProfile::factory()->verified()->create(['stage_name' => 'Older']);
        $this->travel(1)->minutes();
        TalentProfile::factory()->verified()->create(['stage_name' => 'Newer']);

        $result = $this->service->searchTalents([]);

        $items = $result->items();
        $this->assertEquals('Newer', $items[0]->stage_name);
    }
}
