<?php

namespace Tests\Unit\Models;

use App\Enums\TalentLevel;
use App\Models\Category;
use App\Models\TalentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TalentProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_talent_profile_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $profile = TalentProfile::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $profile->user);
        $this->assertEquals($user->id, $profile->user->id);
    }

    public function test_talent_profile_belongs_to_category(): void
    {
        $category = Category::factory()->create();
        $profile = TalentProfile::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(Category::class, $profile->category);
        $this->assertEquals($category->id, $profile->category->id);
    }

    public function test_talent_level_cast_to_enum(): void
    {
        $profile = TalentProfile::factory()->create([
            'talent_level' => TalentLevel::CONFIRME,
        ]);

        $profile->refresh();

        $this->assertInstanceOf(TalentLevel::class, $profile->talent_level);
        $this->assertEquals(TalentLevel::CONFIRME, $profile->talent_level);
        $this->assertEquals('Confirmé', $profile->talent_level->label());
    }

    public function test_social_links_cast_to_array(): void
    {
        $links = [
            'instagram' => 'https://instagram.com/test',
            'youtube' => 'https://youtube.com/test',
        ];

        $profile = TalentProfile::factory()->create([
            'social_links' => $links,
        ]);

        $profile->refresh();

        $this->assertIsArray($profile->social_links);
        $this->assertEquals('https://instagram.com/test', $profile->social_links['instagram']);
    }
}
