<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_all_root_categories(): void
    {
        Category::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'description', 'color_hex'],
                ],
            ])
            ->assertJsonCount(5, 'data');
    }

    public function test_categories_include_children(): void
    {
        $parent = Category::factory()->create(['name' => 'Musique']);
        Category::factory()->withParent($parent)->create(['name' => 'Jazz']);
        Category::factory()->withParent($parent)->create(['name' => 'Rap']);

        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonCount(2, 'data.0.children');
    }

    public function test_categories_accessible_without_auth(): void
    {
        Category::factory()->create();

        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200);
    }
}
