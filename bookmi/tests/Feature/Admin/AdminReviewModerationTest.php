<?php

namespace Tests\Feature\Admin;

use App\Models\Review;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminReviewModerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->admin()->create();
        $user->assignRole('admin_ceo');

        return $user;
    }

    private function reportedReview(): Review
    {
        return Review::factory()->create([
            'is_reported'   => true,
            'report_reason' => 'Contenu offensant',
            'reported_at'   => now(),
        ]);
    }

    #[Test]
    public function authenticated_user_can_report_a_review(): void
    {
        $user   = User::factory()->create();
        $review = Review::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/reviews/{$review->id}/report", ['reason' => 'Contenu abusif'])
            ->assertOk();

        $this->assertDatabaseHas('reviews', [
            'id'          => $review->id,
            'is_reported' => true,
        ]);
    }

    #[Test]
    public function admin_can_list_reported_reviews(): void
    {
        $admin = $this->admin();
        $this->reportedReview();
        $this->reportedReview();
        Review::factory()->create(['is_reported' => false]);

        $this->actingAs($admin)
            ->getJson('/admin/reviews/reported')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function admin_can_approve_a_reported_review(): void
    {
        $admin  = $this->admin();
        $review = $this->reportedReview();

        $this->actingAs($admin)
            ->postJson("/admin/reviews/{$review->id}/approve")
            ->assertOk();

        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'is_reported' => false]);
    }

    #[Test]
    public function admin_can_delete_a_reported_review(): void
    {
        $admin  = $this->admin();
        $review = $this->reportedReview();

        $this->actingAs($admin)
            ->deleteJson("/admin/reviews/{$review->id}", ['reason' => 'Contenu inapproprié'])
            ->assertOk();

        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    #[Test]
    public function admin_can_edit_review_content(): void
    {
        $admin  = $this->admin();
        $review = $this->reportedReview();

        $this->actingAs($admin)
            ->patchJson("/admin/reviews/{$review->id}", [
                'comment' => 'Contenu masqué.',
                'reason'  => 'Masquage contenu inapproprié',
            ])
            ->assertOk();

        $this->assertDatabaseHas('reviews', [
            'id'          => $review->id,
            'comment'     => 'Contenu masqué.',
            'is_reported' => false,
        ]);
    }

    #[Test]
    public function cannot_approve_review_that_was_not_reported(): void
    {
        $admin  = $this->admin();
        $review = Review::factory()->create(['is_reported' => false]);

        $this->actingAs($admin)
            ->postJson("/admin/reviews/{$review->id}/approve")
            ->assertStatus(422);
    }
}
