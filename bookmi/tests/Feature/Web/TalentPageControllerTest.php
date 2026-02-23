<?php

namespace Tests\Feature\Web;

use App\Models\TalentProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TalentPageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_talent_page_returns_200(): void
    {
        $talent = TalentProfile::factory()->verified()->create();

        $response = $this->get("/talents/{$talent->slug}");

        $response->assertStatus(200);
    }

    public function test_web_talent_page_contains_schema_org(): void
    {
        $talent = TalentProfile::factory()->verified()->create([
            'stage_name' => 'DJ Test Schema',
        ]);

        $response = $this->get("/talents/{$talent->slug}");

        $response->assertStatus(200)
            ->assertSee('application/ld+json', false)
            ->assertSee('"@type"', false)
            ->assertSee('DJ Test Schema', false);
    }

    public function test_web_talent_page_contains_open_graph_tags(): void
    {
        $talent = TalentProfile::factory()->verified()->create([
            'stage_name' => 'DJ OG Test',
        ]);

        $response = $this->get("/talents/{$talent->slug}");

        $response->assertStatus(200)
            ->assertSee('og:title', false)
            ->assertSee('og:description', false)
            ->assertSee('og:url', false)
            ->assertSee('og:type', false)
            ->assertSee('DJ OG Test', false);
    }

    public function test_web_talent_page_returns_404_for_unknown_slug(): void
    {
        $response = $this->get('/talents/slug-inexistant');

        $response->assertStatus(404);
    }

    public function test_web_talent_page_returns_404_for_unverified_talent(): void
    {
        $talent = TalentProfile::factory()->create(['is_verified' => false]);

        $response = $this->get("/talents/{$talent->slug}");

        $response->assertStatus(404);
    }
}
