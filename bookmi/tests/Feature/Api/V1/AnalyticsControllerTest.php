<?php

namespace Tests\Feature\Api\V1;

use App\Enums\BookingStatus;
use App\Models\BookingRequest;
use App\Models\TalentProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnalyticsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function createTalentWithProfile(): array
    {
        $user = User::factory()->create();
        $user->assignRole('talent');
        $talent = TalentProfile::factory()->create(['user_id' => $user->id]);

        return [$user, $talent];
    }

    // ─────────────────────────────────────────────
    // Story 7.8 — Analytics talent
    // ─────────────────────────────────────────────

    #[Test]
    public function talent_can_view_analytics_dashboard(): void
    {
        [$user, $talent] = $this->createTalentWithProfile();

        $client = User::factory()->create();
        BookingRequest::factory()->count(3)->create([
            'talent_profile_id' => $talent->id,
            'client_id' => $client->id,
            'status' => BookingStatus::Completed->value,
            'event_date' => now()->format('Y-m-d'),
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/v1/me/analytics');

        $response->assertStatus(200)
            ->assertJsonPath('data.talent_profile_id', $talent->id)
            ->assertJsonStructure([
                'data' => [
                    'stage_name',
                    'talent_level',
                    'average_rating',
                    'total_bookings',
                    'pending_bookings',
                    'current_month_revenue_xof',
                    'bookings_by_status',
                    'monthly_revenue',
                    'rating_history',
                    'profile_views',
                    'top_cities',
                ],
            ]);
    }

    #[Test]
    public function analytics_top_cities_returns_correct_ranking(): void
    {
        [$user, $talent] = $this->createTalentWithProfile();
        $client = User::factory()->create();

        // 3 bookings in Abidjan, 1 in Dakar (completed or paid)
        BookingRequest::factory()->count(3)->create([
            'talent_profile_id' => $talent->id,
            'client_id'         => $client->id,
            'status'            => BookingStatus::Completed->value,
            'event_location'    => 'Abidjan',
        ]);
        BookingRequest::factory()->create([
            'talent_profile_id' => $talent->id,
            'client_id'         => $client->id,
            'status'            => BookingStatus::Paid->value,
            'event_location'    => 'Dakar',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/analytics');

        $response->assertStatus(200);
        $topCities = $response->json('data.top_cities');
        $this->assertNotEmpty($topCities);
        $this->assertEquals('Abidjan', $topCities[0]['city']);
        $this->assertEquals(3, $topCities[0]['count']);
        $this->assertEquals('Dakar', $topCities[1]['city']);
        $this->assertEquals(1, $topCities[1]['count']);
    }

    #[Test]
    public function analytics_top_cities_ignores_pending_bookings(): void
    {
        [$user, $talent] = $this->createTalentWithProfile();
        $client = User::factory()->create();

        // Only pending booking — should NOT appear in top_cities
        BookingRequest::factory()->create([
            'talent_profile_id' => $talent->id,
            'client_id'         => $client->id,
            'status'            => BookingStatus::Pending->value,
            'event_location'    => 'Bamako',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/analytics');

        $response->assertStatus(200);
        $this->assertEmpty($response->json('data.top_cities'));
    }

    #[Test]
    public function analytics_returns_404_if_no_talent_profile(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client, 'sanctum');

        $response = $this->getJson('/api/v1/me/analytics');

        $response->assertStatus(404)
            ->assertJsonPath('error.code', 'TALENT_PROFILE_NOT_FOUND');
    }

    #[Test]
    public function analytics_returns_empty_when_no_bookings(): void
    {
        [$user] = $this->createTalentWithProfile();

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/v1/me/analytics');

        $response->assertStatus(200)
            ->assertJsonPath('data.total_bookings', 0)
            ->assertJsonPath('data.pending_bookings', 0)
            ->assertJsonPath('data.current_month_revenue_xof', 0);
    }
}
