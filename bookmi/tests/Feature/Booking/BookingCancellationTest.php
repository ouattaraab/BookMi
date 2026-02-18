<?php

namespace Tests\Feature\Booking;

use App\Enums\BookingStatus;
use App\Models\BookingRequest;
use App\Models\ServicePackage;
use App\Models\TalentProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingCancellationTest extends TestCase
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

    // ─── AC1: Remboursement intégral (>= J-14) ────────────────────────────────

    #[Test]
    public function client_can_cancel_with_full_refund_14_days_before(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createBooking($client, $talent, $package, [
            'event_date' => now()->addDays(20)->format('Y-m-d'),
        ]);

        $this->actingAs($client, 'sanctum');

        $response = $this->postJson("/api/v1/booking_requests/{$booking->id}/cancel");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', BookingStatus::Cancelled->value)
            ->assertJsonPath('data.cancellation_policy_applied', 'full_refund')
            ->assertJsonPath('data.refund_amount', $booking->total_amount);
    }

    #[Test]
    public function client_can_cancel_exactly_14_days_before_with_full_refund(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createBooking($client, $talent, $package, [
            'event_date' => now()->addDays(14)->format('Y-m-d'),
        ]);

        $this->actingAs($client, 'sanctum');

        $this->postJson("/api/v1/booking_requests/{$booking->id}/cancel")
            ->assertStatus(200)
            ->assertJsonPath('data.cancellation_policy_applied', 'full_refund');
    }

    // ─── AC2: Remboursement partiel (J-7 à J-14) ──────────────────────────────

    #[Test]
    public function client_gets_partial_refund_between_7_and_14_days(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createBooking($client, $talent, $package, [
            'event_date' => now()->addDays(10)->format('Y-m-d'),
        ]);

        $this->actingAs($client, 'sanctum');

        $expectedRefund = (int) round($booking->total_amount * 50 / 100);

        $this->postJson("/api/v1/booking_requests/{$booking->id}/cancel")
            ->assertStatus(200)
            ->assertJsonPath('data.status', BookingStatus::Cancelled->value)
            ->assertJsonPath('data.cancellation_policy_applied', 'partial_refund')
            ->assertJsonPath('data.refund_amount', $expectedRefund);
    }

    #[Test]
    public function client_gets_partial_refund_exactly_7_days_before(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createBooking($client, $talent, $package, [
            'event_date' => now()->addDays(7)->format('Y-m-d'),
        ]);

        $this->actingAs($client, 'sanctum');

        $this->postJson("/api/v1/booking_requests/{$booking->id}/cancel")
            ->assertStatus(200)
            ->assertJsonPath('data.cancellation_policy_applied', 'partial_refund');
    }

    // ─── AC3: Médiation requise (J-2 à J-7) ───────────────────────────────────

    #[Test]
    public function cancellation_requires_mediation_between_2_and_7_days(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createBooking($client, $talent, $package, [
            'event_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $this->actingAs($client, 'sanctum');

        $this->postJson("/api/v1/booking_requests/{$booking->id}/cancel")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'BOOKING_CANCELLATION_MEDIATION_REQUIRED');

        $this->assertDatabaseHas('booking_requests', [
            'id'     => $booking->id,
            'status' => BookingStatus::Accepted->value,
        ]);
    }

    // ─── AC4: Bloqué (< J-2) ──────────────────────────────────────────────────

    #[Test]
    public function cancellation_is_blocked_less_than_2_days_before(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createBooking($client, $talent, $package, [
            'event_date' => now()->addDays(1)->format('Y-m-d'),
        ]);

        $this->actingAs($client, 'sanctum');

        $this->postJson("/api/v1/booking_requests/{$booking->id}/cancel")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'BOOKING_CANCELLATION_NOT_ALLOWED');
    }

    // ─── AC5: Confirmed status → Cancelled autorisé ───────────────────────────

    #[Test]
    public function client_can_cancel_confirmed_booking(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createBooking($client, $talent, $package, [
            'status'     => BookingStatus::Confirmed,
            'event_date' => now()->addDays(20)->format('Y-m-d'),
        ]);

        $this->actingAs($client, 'sanctum');

        $this->postJson("/api/v1/booking_requests/{$booking->id}/cancel")
            ->assertStatus(200)
            ->assertJsonPath('data.status', BookingStatus::Cancelled->value);
    }

    // ─── AC6: Seul le client peut annuler ─────────────────────────────────────

    #[Test]
    public function talent_cannot_cancel_booking(): void
    {
        [$talentUser, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createBooking($client, $talent, $package, [
            'event_date' => now()->addDays(20)->format('Y-m-d'),
        ]);

        $this->actingAs($talentUser, 'sanctum');

        $this->postJson("/api/v1/booking_requests/{$booking->id}/cancel")
            ->assertStatus(403);
    }

    #[Test]
    public function third_party_cannot_cancel_booking(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();
        $client     = $this->createClientUser();
        $thirdParty = $this->createClientUser();
        $booking    = $this->createBooking($client, $talent, $package, [
            'event_date' => now()->addDays(20)->format('Y-m-d'),
        ]);

        $this->actingAs($thirdParty, 'sanctum');

        $this->postJson("/api/v1/booking_requests/{$booking->id}/cancel")
            ->assertStatus(403);
    }

    // ─── AC7: Statut invalide → 422 ───────────────────────────────────────────

    #[Test]
    public function cannot_cancel_already_cancelled_booking(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createBooking($client, $talent, $package, [
            'status'     => BookingStatus::Cancelled,
            'event_date' => now()->addDays(20)->format('Y-m-d'),
        ]);

        $this->actingAs($client, 'sanctum');

        $this->postJson("/api/v1/booking_requests/{$booking->id}/cancel")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'BOOKING_INVALID_TRANSITION');
    }

    #[Test]
    public function cannot_cancel_completed_booking(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createBooking($client, $talent, $package, [
            'status'     => BookingStatus::Completed,
            'event_date' => now()->addDays(20)->format('Y-m-d'),
        ]);

        $this->actingAs($client, 'sanctum');

        $this->postJson("/api/v1/booking_requests/{$booking->id}/cancel")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'BOOKING_INVALID_TRANSITION');
    }
}
