<?php

namespace Tests\Feature\Web\Manager;

use App\Enums\BookingStatus;
use App\Models\BookingRequest;
use App\Models\TalentProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BookingAcceptRejectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function setupManagerAndTalent(): array
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $talentUser = User::factory()->create();
        $talentUser->assignRole('talent');
        $talent = TalentProfile::factory()->create(['user_id' => $talentUser->id]);
        $talent->managers()->attach($manager->id, ['assigned_at' => now()]);

        return [$manager, $talent];
    }

    /** Manager peut accepter un booking pending de son talent */
    public function test_manager_can_accept_pending_booking(): void
    {
        Queue::fake();
        [$manager, $talent] = $this->setupManagerAndTalent();
        $client = User::factory()->create();

        $booking = BookingRequest::factory()->create([
            'talent_profile_id' => $talent->id,
            'client_id'         => $client->id,
            'status'            => BookingStatus::Pending,
        ]);

        $response = $this->actingAs($manager, 'web')
            ->post("/manager/bookings/{$booking->id}/accept");

        $response->assertRedirect("/manager/bookings/{$booking->id}");
        $this->assertDatabaseHas('booking_requests', [
            'id'     => $booking->id,
            'status' => BookingStatus::Accepted->value,
        ]);
    }

    /** Manager ne peut pas accepter un booking d'un autre talent (403) */
    public function test_manager_cannot_accept_booking_of_other_talent(): void
    {
        Queue::fake();
        [$manager] = $this->setupManagerAndTalent();

        // Another talent not managed by this manager
        $otherTalentUser = User::factory()->create();
        $otherTalent = TalentProfile::factory()->create(['user_id' => $otherTalentUser->id]);
        $client = User::factory()->create();

        $booking = BookingRequest::factory()->create([
            'talent_profile_id' => $otherTalent->id,
            'client_id'         => $client->id,
            'status'            => BookingStatus::Pending,
        ]);

        $response = $this->actingAs($manager, 'web')
            ->post("/manager/bookings/{$booking->id}/accept");

        $response->assertStatus(404);
    }
}
