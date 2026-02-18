<?php

namespace Tests\Feature\Booking;

use App\Enums\BookingStatus;
use App\Enums\RescheduleStatus;
use App\Models\BookingRequest;
use App\Models\RescheduleRequest;
use App\Models\ServicePackage;
use App\Models\TalentProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingRescheduleTest extends TestCase
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

    private function createBooking(
        User $client,
        TalentProfile $talent,
        ServicePackage $package,
        array $attrs = [],
    ): BookingRequest {
        return BookingRequest::create(array_merge([
            'client_id'          => $client->id,
            'talent_profile_id'  => $talent->id,
            'service_package_id' => $package->id,
            'event_date'         => now()->addDays(20)->format('Y-m-d'),
            'event_location'     => 'Cocody, Abidjan',
            'message'            => null,
            'status'             => BookingStatus::Accepted,
            'cachet_amount'      => $package->cachet_amount,
            'commission_amount'  => (int) round($package->cachet_amount * 0.15),
            'total_amount'       => (int) round($package->cachet_amount * 1.15),
        ], $attrs));
    }

    // ─── AC1: Client peut créer un reschedule ──────────────────────────────────

    #[Test]
    public function client_can_create_reschedule_request(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createBooking($client, $talent, $package);

        $this->actingAs($client, 'sanctum');

        $proposedDate = now()->addDays(30)->format('Y-m-d');

        $response = $this->postJson("/api/v1/booking_requests/{$booking->id}/reschedule", [
            'proposed_date' => $proposedDate,
            'message'       => 'Je dois reporter l\'événement.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', RescheduleStatus::Pending->value)
            ->assertJsonPath('data.proposed_date', $proposedDate)
            ->assertJsonPath('data.booking_id', $booking->id);

        $this->assertDatabaseHas('reschedule_requests', [
            'booking_request_id' => $booking->id,
            'requested_by_id'    => $client->id,
            'status'             => RescheduleStatus::Pending->value,
        ]);
    }

    // ─── AC2: Talent peut créer un reschedule ──────────────────────────────────

    #[Test]
    public function talent_can_create_reschedule_request(): void
    {
        [$talentUser, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createBooking($client, $talent, $package);

        $this->actingAs($talentUser, 'sanctum');

        $this->postJson("/api/v1/booking_requests/{$booking->id}/reschedule", [
            'proposed_date' => now()->addDays(30)->format('Y-m-d'),
        ])->assertStatus(201)
            ->assertJsonPath('data.status', RescheduleStatus::Pending->value);
    }

    // ─── AC3: Contrepar­tie peut accepter ──────────────────────────────────────

    #[Test]
    public function counterparty_can_accept_reschedule_and_booking_date_updates(): void
    {
        [$talentUser, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createBooking($client, $talent, $package);

        $proposedDate = now()->addDays(30)->format('Y-m-d');

        $reschedule = RescheduleRequest::create([
            'booking_request_id' => $booking->id,
            'requested_by_id'    => $client->id,
            'proposed_date'      => $proposedDate,
            'status'             => RescheduleStatus::Pending,
        ]);

        $this->actingAs($talentUser, 'sanctum');

        $this->postJson("/api/v1/reschedule_requests/{$reschedule->id}/accept")
            ->assertStatus(200)
            ->assertJsonPath('data.status', RescheduleStatus::Accepted->value);

        $this->assertDatabaseHas('booking_requests', [
            'id'         => $booking->id,
            'event_date' => $proposedDate,
        ]);
    }

    // ─── AC4: Contrepar­tie peut rejeter ───────────────────────────────────────

    #[Test]
    public function counterparty_can_reject_reschedule(): void
    {
        [$talentUser, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createBooking($client, $talent, $package);

        $reschedule = RescheduleRequest::create([
            'booking_request_id' => $booking->id,
            'requested_by_id'    => $client->id,
            'proposed_date'      => now()->addDays(30)->format('Y-m-d'),
            'status'             => RescheduleStatus::Pending,
        ]);

        $this->actingAs($talentUser, 'sanctum');

        $this->postJson("/api/v1/reschedule_requests/{$reschedule->id}/reject")
            ->assertStatus(200)
            ->assertJsonPath('data.status', RescheduleStatus::Rejected->value);

        // Booking date unchanged
        $this->assertDatabaseHas('booking_requests', [
            'id'         => $booking->id,
            'event_date' => $booking->event_date->toDateString(),
        ]);
    }

    // ─── AC5: Impossible de créer si reschedule déjà en attente ───────────────

    #[Test]
    public function cannot_create_reschedule_when_one_is_already_pending(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createBooking($client, $talent, $package);

        // First reschedule
        RescheduleRequest::create([
            'booking_request_id' => $booking->id,
            'requested_by_id'    => $client->id,
            'proposed_date'      => now()->addDays(30)->format('Y-m-d'),
            'status'             => RescheduleStatus::Pending,
        ]);

        $this->actingAs($client, 'sanctum');

        $this->postJson("/api/v1/booking_requests/{$booking->id}/reschedule", [
            'proposed_date' => now()->addDays(35)->format('Y-m-d'),
        ])->assertStatus(422)
            ->assertJsonPath('error.code', 'BOOKING_RESCHEDULE_ALREADY_PENDING');
    }

    // ─── AC6: Requester ne peut pas répondre à sa propre demande ──────────────

    #[Test]
    public function requester_cannot_accept_own_reschedule(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createBooking($client, $talent, $package);

        $reschedule = RescheduleRequest::create([
            'booking_request_id' => $booking->id,
            'requested_by_id'    => $client->id,
            'proposed_date'      => now()->addDays(30)->format('Y-m-d'),
            'status'             => RescheduleStatus::Pending,
        ]);

        $this->actingAs($client, 'sanctum');

        $this->postJson("/api/v1/reschedule_requests/{$reschedule->id}/accept")
            ->assertStatus(403);
    }

    #[Test]
    public function requester_cannot_reject_own_reschedule(): void
    {
        [$talentUser, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createBooking($client, $talent, $package);

        $reschedule = RescheduleRequest::create([
            'booking_request_id' => $booking->id,
            'requested_by_id'    => $talentUser->id,
            'proposed_date'      => now()->addDays(30)->format('Y-m-d'),
            'status'             => RescheduleStatus::Pending,
        ]);

        $this->actingAs($talentUser, 'sanctum');

        $this->postJson("/api/v1/reschedule_requests/{$reschedule->id}/reject")
            ->assertStatus(403);
    }

    // ─── AC7: Tiers ne peut ni créer ni répondre ──────────────────────────────

    #[Test]
    public function third_party_cannot_create_reschedule(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();
        $client     = $this->createClientUser();
        $thirdParty = $this->createClientUser();
        $booking    = $this->createBooking($client, $talent, $package);

        $this->actingAs($thirdParty, 'sanctum');

        $this->postJson("/api/v1/booking_requests/{$booking->id}/reschedule", [
            'proposed_date' => now()->addDays(30)->format('Y-m-d'),
        ])->assertStatus(403);
    }

    // ─── AC8: Statut invalide → 422 ───────────────────────────────────────────

    #[Test]
    public function cannot_reschedule_cancelled_booking(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createBooking($client, $talent, $package, [
            'status' => BookingStatus::Cancelled,
        ]);

        $this->actingAs($client, 'sanctum');

        $this->postJson("/api/v1/booking_requests/{$booking->id}/reschedule", [
            'proposed_date' => now()->addDays(30)->format('Y-m-d'),
        ])->assertStatus(422)
            ->assertJsonPath('error.code', 'BOOKING_INVALID_TRANSITION');
    }

    // ─── AC9: Même date → 422 ─────────────────────────────────────────────────

    #[Test]
    public function cannot_reschedule_to_same_date(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createBooking($client, $talent, $package, [
            'event_date' => now()->addDays(20)->format('Y-m-d'),
        ]);

        $this->actingAs($client, 'sanctum');

        $this->postJson("/api/v1/booking_requests/{$booking->id}/reschedule", [
            'proposed_date' => now()->addDays(20)->format('Y-m-d'),
        ])->assertStatus(422)
            ->assertJsonPath('error.code', 'BOOKING_RESCHEDULE_SAME_DATE');
    }

    // ─── AC10: Reschedule non-pending → 422 ───────────────────────────────────

    #[Test]
    public function cannot_accept_already_accepted_reschedule(): void
    {
        [$talentUser, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createBooking($client, $talent, $package);

        $reschedule = RescheduleRequest::create([
            'booking_request_id' => $booking->id,
            'requested_by_id'    => $client->id,
            'proposed_date'      => now()->addDays(30)->format('Y-m-d'),
            'status'             => RescheduleStatus::Accepted,
            'responded_at'       => now(),
        ]);

        $this->actingAs($talentUser, 'sanctum');

        $this->postJson("/api/v1/reschedule_requests/{$reschedule->id}/accept")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'BOOKING_RESCHEDULE_NOT_PENDING');
    }
}
