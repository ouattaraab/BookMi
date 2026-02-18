<?php

namespace Tests\Feature\Api\V1;

use App\Enums\BookingStatus;
use App\Enums\EscrowStatus;
use App\Events\EscrowReleased;
use App\Models\BookingRequest;
use App\Models\EscrowHold;
use App\Models\Transaction;
use App\Enums\TransactionStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EscrowControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([ThrottleRequests::class, ThrottleRequestsWithRedis::class]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function createPaidBookingWithEscrow(User $client): array
    {
        $booking = BookingRequest::factory()->paid()->create(['client_id' => $client->id]);

        $transaction = Transaction::create([
            'booking_request_id' => $booking->id,
            'payment_method'     => 'card',
            'amount'             => $booking->total_amount,
            'currency'           => 'XOF',
            'gateway'            => 'paystack',
            'status'             => TransactionStatus::Succeeded->value,
            'idempotency_key'    => 'escrow-test-key-' . uniqid(),
            'initiated_at'       => now(),
        ]);

        $hold = EscrowHold::create([
            'transaction_id'       => $transaction->id,
            'booking_request_id'   => $booking->id,
            'cachet_amount'        => $booking->cachet_amount,
            'commission_amount'    => $booking->commission_amount,
            'total_amount'         => $booking->total_amount,
            'status'               => EscrowStatus::Held->value,
            'held_at'              => now(),
            'release_scheduled_at' => now()->addHours(48),
        ]);

        return [$booking, $hold, $transaction];
    }

    // ── AC1: confirm_delivery ─────────────────────────────────────────────────

    public function test_client_can_confirm_delivery_and_escrow_is_released(): void
    {
        Event::fake([EscrowReleased::class]);

        $client = User::factory()->create();
        [$booking, $hold] = $this->createPaidBookingWithEscrow($client);

        $response = $this->actingAs($client, 'sanctum')
            ->postJson("/api/v1/booking_requests/{$booking->id}/confirm_delivery");

        $response->assertStatus(200)
            ->assertJsonPath('booking_status', BookingStatus::Confirmed->value);

        $this->assertDatabaseHas('escrow_holds', [
            'id'     => $hold->id,
            'status' => EscrowStatus::Released->value,
        ]);
        $this->assertNotNull(EscrowHold::find($hold->id)->released_at);

        $this->assertDatabaseHas('booking_requests', [
            'id'     => $booking->id,
            'status' => BookingStatus::Confirmed->value,
        ]);

        Event::assertDispatched(EscrowReleased::class, function (EscrowReleased $event) use ($hold) {
            return $event->escrowHold->id === $hold->id;
        });
    }

    public function test_non_owner_cannot_confirm_delivery(): void
    {
        $client    = User::factory()->create();
        $otherUser = User::factory()->create();
        [$booking] = $this->createPaidBookingWithEscrow($client);

        $response = $this->actingAs($otherUser, 'sanctum')
            ->postJson("/api/v1/booking_requests/{$booking->id}/confirm_delivery");

        $response->assertStatus(403);
    }

    public function test_confirm_delivery_returns_422_when_booking_not_paid(): void
    {
        $client  = User::factory()->create();
        $booking = BookingRequest::factory()->accepted()->create(['client_id' => $client->id]);

        $response = $this->actingAs($client, 'sanctum')
            ->postJson("/api/v1/booking_requests/{$booking->id}/confirm_delivery");

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'ESCROW_BOOKING_NOT_CONFIRMABLE');
    }

    public function test_confirm_delivery_returns_422_when_no_held_escrow(): void
    {
        $client  = User::factory()->create();
        $booking = BookingRequest::factory()->paid()->create(['client_id' => $client->id]);
        // No EscrowHold created

        $response = $this->actingAs($client, 'sanctum')
            ->postJson("/api/v1/booking_requests/{$booking->id}/confirm_delivery");

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'ESCROW_NOT_HELD');
    }

    public function test_confirm_delivery_is_idempotent(): void
    {
        Event::fake([EscrowReleased::class]);

        $client = User::factory()->create();
        [$booking, $hold] = $this->createPaidBookingWithEscrow($client);

        // First call
        $this->actingAs($client, 'sanctum')
            ->postJson("/api/v1/booking_requests/{$booking->id}/confirm_delivery")
            ->assertStatus(200);

        // Booking is now Confirmed — second call should fail (not in Paid status anymore)
        $response = $this->actingAs($client, 'sanctum')
            ->postJson("/api/v1/booking_requests/{$booking->id}/confirm_delivery");

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'ESCROW_BOOKING_NOT_CONFIRMABLE');

        // Escrow still released once
        Event::assertDispatchedTimes(EscrowReleased::class, 1);
    }

    public function test_confirm_delivery_returns_401_when_unauthenticated(): void
    {
        $client  = User::factory()->create();
        $booking = BookingRequest::factory()->paid()->create(['client_id' => $client->id]);

        $response = $this->postJson("/api/v1/booking_requests/{$booking->id}/confirm_delivery");

        $response->assertStatus(401);
    }

    public function test_confirm_delivery_returns_404_for_unknown_booking(): void
    {
        $client = User::factory()->create();

        $response = $this->actingAs($client, 'sanctum')
            ->postJson('/api/v1/booking_requests/99999/confirm_delivery');

        $response->assertStatus(404);
    }
}
