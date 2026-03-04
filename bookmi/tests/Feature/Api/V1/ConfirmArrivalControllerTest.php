<?php

namespace Tests\Feature\Api\V1;

use App\Enums\BookingStatus;
use App\Enums\EscrowStatus;
use App\Enums\TrackingStatus;
use App\Enums\UserRole;
use App\Models\BookingRequest;
use App\Models\EscrowHold;
use App\Models\TalentProfile;
use App\Models\TrackingEvent;
use App\Models\Transaction;
use App\Enums\TransactionStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Tests\TestCase;

class ConfirmArrivalControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([ThrottleRequests::class, ThrottleRequestsWithRedis::class]);
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeBookingWithTalent(BookingStatus $status = BookingStatus::Paid): array
    {
        $client = User::factory()->create();
        $client->assignRole(UserRole::CLIENT->value);

        $talent = User::factory()->create();
        $talent->assignRole(UserRole::TALENT->value);
        $profile = TalentProfile::factory()->create(['user_id' => $talent->id]);

        $booking = BookingRequest::factory()->create([
            'client_id'         => $client->id,
            'talent_profile_id' => $profile->id,
            'status'            => $status,
        ]);

        return [$booking, $talent, $client, $profile];
    }

    private function createEscrowHold(BookingRequest $booking): EscrowHold
    {
        $transaction = Transaction::create([
            'booking_request_id' => $booking->id,
            'payment_method'     => 'card',
            'amount'             => $booking->total_amount,
            'currency'           => 'XOF',
            'gateway'            => 'paystack',
            'status'             => TransactionStatus::Succeeded->value,
            'idempotency_key'    => 'confirm-arrival-test-' . uniqid(),
            'initiated_at'       => now(),
        ]);

        return EscrowHold::create([
            'transaction_id'       => $transaction->id,
            'booking_request_id'   => $booking->id,
            'cachet_amount'        => $booking->cachet_amount,
            'commission_amount'    => $booking->commission_amount,
            'total_amount'         => $booking->total_amount,
            'status'               => EscrowStatus::Held->value,
            'held_at'              => now(),
            'release_scheduled_at' => now()->addHours(48),
        ]);
    }

    private function addArrivedEvent(BookingRequest $booking, User $talent): TrackingEvent
    {
        return TrackingEvent::factory()->create([
            'booking_request_id' => $booking->id,
            'updated_by'         => $talent->id,
            'status'             => TrackingStatus::Arrived,
        ]);
    }

    // ── Tests ─────────────────────────────────────────────────────────────────

    public function test_client_can_confirm_arrival_and_escrow_is_released(): void
    {
        [$booking, $talent, $client] = $this->makeBookingWithTalent(BookingStatus::Paid);
        $this->addArrivedEvent($booking, $talent);
        $hold = $this->createEscrowHold($booking);

        $response = $this->actingAs($client)->postJson(
            "/api/v1/booking_requests/{$booking->id}/confirm-arrival",
        );

        $response->assertOk();
        $response->assertJsonPath('data.booking_status', BookingStatus::Confirmed->value);
        $this->assertNotNull($response->json('data.client_confirmed_arrival_at'));

        $this->assertDatabaseHas('escrow_holds', [
            'id'     => $hold->id,
            'status' => EscrowStatus::Released->value,
        ]);
    }

    public function test_client_confirmed_arrival_at_is_recorded(): void
    {
        [$booking, $talent, $client] = $this->makeBookingWithTalent(BookingStatus::Paid);
        $this->addArrivedEvent($booking, $talent);
        $this->createEscrowHold($booking);

        $this->actingAs($client)->postJson(
            "/api/v1/booking_requests/{$booking->id}/confirm-arrival",
        )->assertOk();

        $this->assertDatabaseHas('booking_requests', [
            'id' => $booking->id,
        ]);
        $this->assertNotNull($booking->fresh()->client_confirmed_arrival_at);
    }

    public function test_confirm_arrival_is_idempotent_returns_409(): void
    {
        [$booking, $talent, $client] = $this->makeBookingWithTalent(BookingStatus::Paid);
        $this->addArrivedEvent($booking, $talent);
        $this->createEscrowHold($booking);

        // First confirmation
        $this->actingAs($client)->postJson(
            "/api/v1/booking_requests/{$booking->id}/confirm-arrival",
        )->assertOk();

        // Second confirmation — must be idempotent
        $this->actingAs($client)->postJson(
            "/api/v1/booking_requests/{$booking->id}/confirm-arrival",
        )->assertStatus(409)
         ->assertJsonPath('error.code', 'ALREADY_CONFIRMED');
    }

    public function test_confirm_arrival_fails_if_talent_not_arrived(): void
    {
        [$booking, $talent, $client] = $this->makeBookingWithTalent(BookingStatus::Paid);

        // Only "preparing" — talent has NOT yet signalled arrival
        TrackingEvent::factory()->create([
            'booking_request_id' => $booking->id,
            'updated_by'         => $talent->id,
            'status'             => TrackingStatus::Preparing,
        ]);

        $this->actingAs($client)->postJson(
            "/api/v1/booking_requests/{$booking->id}/confirm-arrival",
        )->assertUnprocessable()
         ->assertJsonPath('error.code', 'TALENT_NOT_ARRIVED');
    }

    public function test_talent_cannot_confirm_their_own_arrival(): void
    {
        [$booking, $talent, $client] = $this->makeBookingWithTalent(BookingStatus::Paid);
        $this->addArrivedEvent($booking, $talent);

        $this->actingAs($talent)->postJson(
            "/api/v1/booking_requests/{$booking->id}/confirm-arrival",
        )->assertForbidden()
         ->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_confirm_arrival_fails_on_pending_booking(): void
    {
        [$booking, $talent, $client] = $this->makeBookingWithTalent(BookingStatus::Pending);
        $this->addArrivedEvent($booking, $talent);

        $this->actingAs($client)->postJson(
            "/api/v1/booking_requests/{$booking->id}/confirm-arrival",
        )->assertUnprocessable()
         ->assertJsonPath('error.code', 'INVALID_STATUS');
    }

    public function test_confirm_arrival_succeeds_on_confirmed_booking_without_escrow_release(): void
    {
        // Booking already Confirmed (escrow previously released) — just records timestamp
        [$booking, $talent, $client] = $this->makeBookingWithTalent(BookingStatus::Confirmed);
        $this->addArrivedEvent($booking, $talent);

        $response = $this->actingAs($client)->postJson(
            "/api/v1/booking_requests/{$booking->id}/confirm-arrival",
        );

        $response->assertOk();
        $response->assertJsonPath('data.booking_status', BookingStatus::Confirmed->value);
        $this->assertNotNull($booking->fresh()->client_confirmed_arrival_at);
    }

    public function test_unauthenticated_user_receives_401(): void
    {
        [$booking] = $this->makeBookingWithTalent();

        $this->postJson(
            "/api/v1/booking_requests/{$booking->id}/confirm-arrival",
        )->assertUnauthorized();
    }

    public function test_push_notification_dispatched_to_talent(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        [$booking, $talent, $client] = $this->makeBookingWithTalent(BookingStatus::Paid);
        $this->addArrivedEvent($booking, $talent);
        $this->createEscrowHold($booking);

        $this->actingAs($client)->postJson(
            "/api/v1/booking_requests/{$booking->id}/confirm-arrival",
        )->assertOk();

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\SendPushNotification::class);
    }
}
