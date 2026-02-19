<?php

namespace Tests\Feature\Api\V1;

use App\Enums\BookingStatus;
use App\Models\BookingRequest;
use App\Models\CalendarSlot;
use App\Models\TalentProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ManagerBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function createManager(): User
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        return $manager;
    }

    private function createTalentWithProfile(): array
    {
        $user = User::factory()->create();
        $user->assignRole('talent');
        $talent = TalentProfile::factory()->create(['user_id' => $user->id]);

        return [$user, $talent];
    }

    // ─────────────────────────────────────────────
    // Story 7.5 — Booking validation by manager
    // ─────────────────────────────────────────────

    #[Test]
    public function manager_can_accept_pending_booking(): void
    {
        $manager = $this->createManager();
        [, $talent] = $this->createTalentWithProfile();
        $manager->managedTalents()->attach($talent->id, ['assigned_at' => now()]);

        $client = User::factory()->create();
        $booking = BookingRequest::factory()->create([
            'talent_profile_id' => $talent->id,
            'client_id' => $client->id,
            'status' => BookingStatus::Pending->value,
        ]);

        $this->actingAs($manager, 'sanctum');

        $response = $this->postJson("/api/v1/manager/talents/{$talent->id}/bookings/{$booking->id}/accept");

        $response->assertStatus(200)
            ->assertJsonPath('data.message', 'Réservation acceptée.');

        $this->assertDatabaseHas('booking_requests', [
            'id' => $booking->id,
            'status' => BookingStatus::Accepted->value,
        ]);
    }

    #[Test]
    public function manager_can_reject_pending_booking_with_reason(): void
    {
        $manager = $this->createManager();
        [, $talent] = $this->createTalentWithProfile();
        $manager->managedTalents()->attach($talent->id, ['assigned_at' => now()]);

        $client = User::factory()->create();
        $booking = BookingRequest::factory()->create([
            'talent_profile_id' => $talent->id,
            'client_id' => $client->id,
            'status' => BookingStatus::Pending->value,
        ]);

        $this->actingAs($manager, 'sanctum');

        $response = $this->postJson("/api/v1/manager/talents/{$talent->id}/bookings/{$booking->id}/reject", [
            'reason' => 'Planning complet ce jour-là.',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.message', 'Réservation refusée.');

        $this->assertDatabaseHas('booking_requests', [
            'id' => $booking->id,
            'status' => BookingStatus::Cancelled->value,
            'reject_reason' => 'Planning complet ce jour-là.',
        ]);
    }

    #[Test]
    public function manager_cannot_accept_booking_of_unassigned_talent(): void
    {
        $manager = $this->createManager();
        [, $talent] = $this->createTalentWithProfile();
        // NOT attaching talent to manager

        $client = User::factory()->create();
        $booking = BookingRequest::factory()->create([
            'talent_profile_id' => $talent->id,
            'client_id' => $client->id,
            'status' => BookingStatus::Pending->value,
        ]);

        $this->actingAs($manager, 'sanctum');

        $response = $this->postJson("/api/v1/manager/talents/{$talent->id}/bookings/{$booking->id}/accept");

        $response->assertStatus(403)
            ->assertJsonPath('error.code', 'MANAGER_UNAUTHORIZED');
    }

    // ─────────────────────────────────────────────
    // Story 7.4 — Calendar management
    // ─────────────────────────────────────────────

    #[Test]
    public function manager_can_create_calendar_slot_for_assigned_talent(): void
    {
        $manager = $this->createManager();
        [, $talent] = $this->createTalentWithProfile();
        $manager->managedTalents()->attach($talent->id, ['assigned_at' => now()]);

        $this->actingAs($manager, 'sanctum');

        $response = $this->postJson("/api/v1/manager/talents/{$talent->id}/calendar_slots", [
            'date' => '2026-03-15',
            'status' => 'available',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('calendar_slots', [
            'talent_profile_id' => $talent->id,
        ]);
    }

    #[Test]
    public function manager_can_delete_calendar_slot_for_assigned_talent(): void
    {
        $manager = $this->createManager();
        [, $talent] = $this->createTalentWithProfile();
        $manager->managedTalents()->attach($talent->id, ['assigned_at' => now()]);

        $slot = CalendarSlot::factory()->create(['talent_profile_id' => $talent->id]);

        $this->actingAs($manager, 'sanctum');

        $response = $this->deleteJson("/api/v1/manager/talents/{$talent->id}/calendar_slots/{$slot->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('calendar_slots', ['id' => $slot->id]);
    }
}
