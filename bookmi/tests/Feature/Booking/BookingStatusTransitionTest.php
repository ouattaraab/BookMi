<?php

namespace Tests\Feature\Booking;

use App\Enums\BookingStatus;
use App\Enums\CalendarSlotStatus;
use App\Events\BookingAccepted;
use App\Events\BookingCancelled;
use App\Models\BookingRequest;
use App\Models\CalendarSlot;
use App\Models\ServicePackage;
use App\Models\TalentProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function createClientUser(): User
    {
        $user = User::factory()->create([
            'phone_verified_at' => now(),
            'is_active'         => true,
        ]);
        $user->assignRole('client');

        return $user;
    }

    private function createTalentWithPackage(): array
    {
        $user = User::factory()->create([
            'phone_verified_at' => now(),
            'is_active'         => true,
        ]);
        $user->assignRole('talent');

        $talent  = TalentProfile::factory()->verified()->create(['user_id' => $user->id]);
        $package = ServicePackage::factory()->create([
            'talent_profile_id' => $talent->id,
            'cachet_amount'     => 10_000_000,
            'is_active'         => true,
        ]);

        return [$user, $talent, $package];
    }

    private function createPendingBooking(
        User $client,
        TalentProfile $talent,
        ServicePackage $package,
        array $attrs = [],
    ): BookingRequest {
        return BookingRequest::create(array_merge([
            'client_id'          => $client->id,
            'talent_profile_id'  => $talent->id,
            'service_package_id' => $package->id,
            'event_date'         => now()->addDays(15)->format('Y-m-d'),
            'event_location'     => 'Cocody, Abidjan',
            'message'            => null,
            'status'             => BookingStatus::Pending,
            'cachet_amount'      => $package->cachet_amount,
            'commission_amount'  => (int) round($package->cachet_amount * 0.15),
            'total_amount'       => (int) round($package->cachet_amount * 1.15),
        ], $attrs));
    }

    // ─── AC1: Accepter une réservation ────────────────────────────────────────

    #[Test]
    public function talent_can_accept_pending_booking(): void
    {
        Event::fake([BookingAccepted::class]);

        [$talentUser, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createPendingBooking($client, $talent, $package);

        $this->actingAs($talentUser, 'sanctum');

        $response = $this->postJson("/api/v1/booking_requests/{$booking->id}/accept");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'accepted')
            ->assertJsonPath('data.id', $booking->id);

        $this->assertDatabaseHas('booking_requests', [
            'id'     => $booking->id,
            'status' => 'accepted',
        ]);

        // Calendar slot must be blocked for the event date
        $this->assertDatabaseHas('calendar_slots', [
            'talent_profile_id' => $talent->id,
            'date'              => $booking->event_date->toDateString(),
            'status'            => CalendarSlotStatus::Blocked->value,
        ]);

        Event::assertDispatched(BookingAccepted::class, fn ($e) => $e->booking->id === $booking->id);
    }

    #[Test]
    public function accepting_updates_existing_calendar_slot(): void
    {
        Event::fake([BookingAccepted::class]);

        [$talentUser, $talent, $package] = $this->createTalentWithPackage();
        $client      = $this->createClientUser();
        $eventDate   = now()->addDays(15)->format('Y-m-d');
        $booking     = $this->createPendingBooking($client, $talent, $package, ['event_date' => $eventDate]);

        // Pre-existing slot with a different status
        CalendarSlot::factory()->create([
            'talent_profile_id' => $talent->id,
            'date'              => $eventDate,
            'status'            => CalendarSlotStatus::Available,
        ]);

        $this->actingAs($talentUser, 'sanctum');

        $this->postJson("/api/v1/booking_requests/{$booking->id}/accept")->assertStatus(200);

        $this->assertDatabaseHas('calendar_slots', [
            'talent_profile_id' => $talent->id,
            'date'              => $eventDate,
            'status'            => CalendarSlotStatus::Blocked->value,
        ]);
        $this->assertDatabaseCount('calendar_slots', 1);
    }

    // ─── AC2: Refuser une réservation ─────────────────────────────────────────

    #[Test]
    public function talent_can_reject_pending_booking_with_reason(): void
    {
        Event::fake([BookingCancelled::class]);

        [$talentUser, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createPendingBooking($client, $talent, $package);

        $this->actingAs($talentUser, 'sanctum');

        $response = $this->postJson("/api/v1/booking_requests/{$booking->id}/reject", [
            'reason' => 'Déjà engagé ce jour-là.',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.reject_reason', 'Déjà engagé ce jour-là.');

        $this->assertDatabaseHas('booking_requests', [
            'id'            => $booking->id,
            'status'        => 'rejected',
            'reject_reason' => 'Déjà engagé ce jour-là.',
        ]);

        Event::assertDispatched(BookingCancelled::class, fn ($e) => $e->booking->id === $booking->id);
    }

    #[Test]
    public function talent_can_reject_pending_booking_without_reason(): void
    {
        Event::fake([BookingCancelled::class]);

        [$talentUser, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createPendingBooking($client, $talent, $package);

        $this->actingAs($talentUser, 'sanctum');

        $response = $this->postJson("/api/v1/booking_requests/{$booking->id}/reject");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.reject_reason', null);

        $this->assertDatabaseHas('booking_requests', [
            'id'            => $booking->id,
            'status'        => 'rejected',
            'reject_reason' => null,
        ]);

        Event::assertDispatched(BookingCancelled::class);
    }

    // ─── AC3: Machine à états ─────────────────────────────────────────────────

    #[Test]
    public function cannot_accept_non_pending_booking(): void
    {
        [$talentUser, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createPendingBooking($client, $talent, $package, [
            'status' => BookingStatus::Accepted,
        ]);

        $this->actingAs($talentUser, 'sanctum');

        $this->postJson("/api/v1/booking_requests/{$booking->id}/accept")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'BOOKING_INVALID_TRANSITION');
    }

    #[Test]
    public function cannot_reject_accepted_booking(): void
    {
        // Accepted→Cancelled is allowed by the state machine in general,
        // but the /reject endpoint is ONLY for pending bookings (AC3).
        [$talentUser, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createPendingBooking($client, $talent, $package, [
            'status' => BookingStatus::Accepted,
        ]);

        $this->actingAs($talentUser, 'sanctum');

        $this->postJson("/api/v1/booking_requests/{$booking->id}/reject")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'BOOKING_INVALID_TRANSITION');
    }

    #[Test]
    public function cannot_reject_cancelled_booking(): void
    {
        [$talentUser, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createPendingBooking($client, $talent, $package, [
            'status' => BookingStatus::Cancelled,
        ]);

        $this->actingAs($talentUser, 'sanctum');

        $this->postJson("/api/v1/booking_requests/{$booking->id}/reject")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'BOOKING_INVALID_TRANSITION');
    }

    // ─── AC4: Autorisation ────────────────────────────────────────────────────

    #[Test]
    public function client_cannot_accept_booking(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createPendingBooking($client, $talent, $package);

        $this->actingAs($client, 'sanctum');

        $this->postJson("/api/v1/booking_requests/{$booking->id}/accept")
            ->assertStatus(403);
    }

    #[Test]
    public function client_cannot_reject_booking(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createPendingBooking($client, $talent, $package);

        $this->actingAs($client, 'sanctum');

        $this->postJson("/api/v1/booking_requests/{$booking->id}/reject")
            ->assertStatus(403);
    }

    #[Test]
    public function other_talent_cannot_accept_booking(): void
    {
        [, $talent, $package]     = $this->createTalentWithPackage();
        [$otherTalentUser]        = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createPendingBooking($client, $talent, $package);

        $this->actingAs($otherTalentUser, 'sanctum');

        $this->postJson("/api/v1/booking_requests/{$booking->id}/accept")
            ->assertStatus(403);
    }

    #[Test]
    public function other_talent_cannot_reject_booking(): void
    {
        [, $talent, $package]     = $this->createTalentWithPackage();
        [$otherTalentUser]        = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createPendingBooking($client, $talent, $package);

        $this->actingAs($otherTalentUser, 'sanctum');

        $this->postJson("/api/v1/booking_requests/{$booking->id}/reject")
            ->assertStatus(403);
    }

    // ─── AC5: Validation de la raison ─────────────────────────────────────────

    #[Test]
    public function reject_reason_cannot_exceed_500_chars(): void
    {
        [$talentUser, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createPendingBooking($client, $talent, $package);

        $this->actingAs($talentUser, 'sanctum');

        $this->postJson("/api/v1/booking_requests/{$booking->id}/reject", [
            'reason' => str_repeat('a', 501),
        ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure(['error' => ['details' => ['errors' => ['reason']]]]);
    }
}
