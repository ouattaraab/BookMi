<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRole;
use App\Jobs\CompressPortfolioImage;
use App\Models\PortfolioItem;
use App\Models\TalentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortfolioControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([ThrottleRequests::class, ThrottleRequestsWithRedis::class]);
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        Storage::fake('public');
    }

    private function makeTalent(): array
    {
        $user    = User::factory()->create();
        $user->assignRole(UserRole::TALENT->value);
        $profile = TalentProfile::factory()->create(['user_id' => $user->id]);
        return [$user, $profile];
    }

    // ── GET /talent_profiles/{talentProfile}/portfolio ────────────────────────

    public function test_anyone_can_list_portfolio(): void
    {
        [$user, $profile] = $this->makeTalent();

        PortfolioItem::factory()->count(3)->create(['talent_profile_id' => $profile->id]);

        $response = $this->actingAs($user)->getJson("/api/v1/talent_profiles/{$profile->id}/portfolio");

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    // ── POST /talent_profiles/me/portfolio ────────────────────────────────────

    public function test_talent_can_upload_image_and_job_is_dispatched(): void
    {
        Queue::fake();
        [$user, $profile] = $this->makeTalent();

        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);

        $response = $this->actingAs($user)->postJson('/api/v1/talent_profiles/me/portfolio', [
            'file'    => $file,
            'caption' => 'Concert au Sofitel',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.media_type', 'image');
        $response->assertJsonPath('data.caption', 'Concert au Sofitel');

        Queue::assertPushed(CompressPortfolioImage::class);

        $this->assertDatabaseHas('portfolio_items', [
            'talent_profile_id' => $profile->id,
            'media_type'        => 'image',
            'is_compressed'     => false,
        ]);
    }

    public function test_video_upload_does_not_dispatch_compress_job(): void
    {
        Queue::fake();
        [$user] = $this->makeTalent();

        $file = UploadedFile::fake()->create('video.mp4', 1024, 'video/mp4');

        $this->actingAs($user)->postJson('/api/v1/talent_profiles/me/portfolio', [
            'file' => $file,
        ])->assertCreated();

        Queue::assertNotPushed(CompressPortfolioImage::class);
    }

    public function test_file_is_required(): void
    {
        [$user] = $this->makeTalent();

        $response = $this->actingAs($user)->postJson('/api/v1/talent_profiles/me/portfolio', []);

        $response->assertUnprocessable();
        $this->assertArrayHasKey('file', $response->json('error.details.errors'));
    }

    public function test_non_talent_without_profile_receives_404(): void
    {
        $client = User::factory()->create();
        $client->assignRole(UserRole::CLIENT->value);

        $file = UploadedFile::fake()->image('photo.jpg');

        $this->actingAs($client)->postJson('/api/v1/talent_profiles/me/portfolio', [
            'file' => $file,
        ])->assertNotFound();
    }

    // ── DELETE /talent_profiles/me/portfolio/{portfolioItem} ──────────────────

    public function test_talent_can_delete_own_portfolio_item(): void
    {
        [$user, $profile] = $this->makeTalent();

        $item = PortfolioItem::factory()->create(['talent_profile_id' => $profile->id]);

        $this->actingAs($user)->deleteJson("/api/v1/talent_profiles/me/portfolio/{$item->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('portfolio_items', ['id' => $item->id]);
    }

    public function test_talent_cannot_delete_another_talents_item(): void
    {
        [$user] = $this->makeTalent();
        [$otherUser, $otherProfile] = $this->makeTalent();

        $item = PortfolioItem::factory()->create(['talent_profile_id' => $otherProfile->id]);

        $this->actingAs($user)->deleteJson("/api/v1/talent_profiles/me/portfolio/{$item->id}")
            ->assertForbidden();
    }
}
