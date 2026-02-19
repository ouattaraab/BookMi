<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingStatus;
use App\Models\BookingRequest;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
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

    #[Test]
    public function admin_can_view_dashboard_stats(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)
            ->getJson('/admin/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'users'        => ['total', 'this_week', 'this_month'],
                    'bookings'     => ['total', 'pending', 'completed', 'disputed', 'today'],
                    'revenue'      => ['total_xof', 'commission_xof', 'this_month_xof'],
                    'talents'      => ['total', 'verified'],
                    'dispute_rate',
                ],
            ]);
    }

    #[Test]
    public function non_admin_cannot_access_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/admin/dashboard')
            ->assertStatus(403);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_dashboard(): void
    {
        $this->getJson('/admin/dashboard')
            ->assertStatus(401);
    }

    #[Test]
    public function dashboard_dispute_rate_is_correct(): void
    {
        $admin = $this->admin();

        BookingRequest::factory()->count(2)->create(['status' => BookingStatus::Completed]);
        BookingRequest::factory()->create(['status' => BookingStatus::Disputed]);

        $response = $this->actingAs($admin)->getJson('/admin/dashboard');

        $response->assertOk();
        // 1 disputed out of 3 total = 33.33%
        $this->assertEqualsWithDelta(33.33, $response->json('data.dispute_rate'), 0.1);
    }
}
