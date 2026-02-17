<?php

namespace Tests\Unit\Services;

use App\Exceptions\BookmiException;
use App\Models\Category;
use App\Models\TalentProfile;
use App\Models\User;
use App\Services\TalentProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TalentProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    private TalentProfileService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(TalentProfileService::class);
    }

    public function test_create_profile_succeeds_with_valid_data(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $profile = $this->service->createProfile($user->id, [
            'stage_name' => 'DJ Test',
            'category_id' => $category->id,
            'city' => 'Abidjan',
            'cachet_amount' => 10000,
            'bio' => 'Ma bio',
        ]);

        $this->assertInstanceOf(TalentProfile::class, $profile);
        $this->assertEquals($user->id, $profile->user_id);
        $this->assertEquals('DJ Test', $profile->stage_name);
    }

    public function test_create_profile_fails_if_user_already_has_profile(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        TalentProfile::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $this->expectException(BookmiException::class);

        $this->service->createProfile($user->id, [
            'stage_name' => 'Another Name',
            'category_id' => $category->id,
            'city' => 'Bouaké',
            'cachet_amount' => 5000,
        ]);
    }

    public function test_update_profile_recalculates_completion(): void
    {
        $profile = TalentProfile::factory()->create([
            'bio' => null,
            'profile_completion_percentage' => 0,
        ]);

        $updated = $this->service->updateProfile($profile, [
            'bio' => 'Nouvelle bio ajoutée',
        ]);

        $this->assertEquals(20, $updated->profile_completion_percentage);
    }

    public function test_get_by_slug_returns_correct_profile(): void
    {
        $profile = TalentProfile::factory()->create([
            'stage_name' => 'DJ Slug Test',
        ]);

        $found = $this->service->getBySlug($profile->slug);

        $this->assertNotNull($found);
        $this->assertEquals($profile->id, $found->id);
    }

    public function test_get_by_slug_returns_null_for_nonexistent(): void
    {
        $found = $this->service->getBySlug('slug-inexistant');

        $this->assertNull($found);
    }

    public function test_delete_profile_soft_deletes(): void
    {
        $profile = TalentProfile::factory()->create();

        $result = $this->service->deleteProfile($profile);

        $this->assertTrue($result);
        $this->assertSoftDeleted('talent_profiles', ['id' => $profile->id]);
    }
}
