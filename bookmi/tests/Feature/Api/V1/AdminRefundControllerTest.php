<?php

namespace Tests\Feature\Api\V1;

use App\Enums\BookingStatus;
use App\Enums\EscrowStatus;
use App\Enums\TransactionStatus;
use App\Models\BookingRequest;
use App\Models\EscrowHold;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminRefundControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([ThrottleRequests::class, ThrottleRequestsWithRedis::class]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeAdmin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function makeClient(): User
    {
        return User::factory()->create(['is_admin' => false]);
    }

    /**
     * Create a paid booking with a succeeded transaction (and optional escrow hold).
     *
     * @return array{BookingRequest, Transaction, EscrowHold|null}
     */
    private function createPaidBookingWithTransaction(
        bool $withEscrow = true,
        string $gatewayRef = 'pstk_ref_001',
    ): array {
        $booking = BookingRequest::factory()->paid()->create();

        $transaction = Transaction::create([
            'booking_request_id' => $booking->id,
            'payment_method'     => 'card',
            'amount'             => $booking->total_amount,
            'currency'           => 'XOF',
            'gateway'            => 'paystack',
            'gateway_reference'  => $gatewayRef,
            'status'             => TransactionStatus::Succeeded->value,
            'idempotency_key'    => 'refund-test-' . uniqid(),
            'initiated_at'       => now(),
        ]);

        $hold = null;
        if ($withEscrow) {
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
        }

        return [$booking, $transaction, $hold];
    }

    private function fakePaystackRefundSuccess(string $gatewayRef = 'pstk_ref_001'): void
    {
        Http::fake([
            'https://api.paystack.co/refund' => Http::response([
                'status'  => true,
                'message' => 'Refund has been queued for processing',
                'data'    => [
                    'id'          => 'rfnd_abc123',
                    'transaction' => ['reference' => $gatewayRef],
                    'amount'      => 1000,
                    'status'      => 'pending',
                ],
            ], 200),
        ]);
    }

    private function fakePaystackRefundFailure(): void
    {
        Http::fake([
            'https://api.paystack.co/refund' => Http::response([
                'status'  => false,
                'message' => 'Refund failed',
            ], 400),
        ]);
    }

    private function refundUrl(int $bookingId): string
    {
        return "/api/v1/admin/booking_requests/{$bookingId}/refund";
    }

    // ── AC1: Admin processes a full refund ────────────────────────────────────

    public function test_admin_can_process_refund_and_booking_is_cancelled(): void
    {
        [$booking, $transaction, $hold] = $this->createPaidBookingWithTransaction();
        $this->fakePaystackRefundSuccess();

        $admin = $this->makeAdmin();
        $refundAmount = $booking->total_amount;

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson($this->refundUrl($booking->id), [
                'amount' => $refundAmount,
                'reason' => 'Litige résolu en faveur du client.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Remboursement effectué avec succès.');

        // Transaction marked as Refunded
        $this->assertDatabaseHas('transactions', [
            'id'           => $transaction->id,
            'status'       => TransactionStatus::Refunded->value,
            'refund_amount' => $refundAmount,
            'refund_reason' => 'Litige résolu en faveur du client.',
            'refund_reference' => 'rfnd_abc123',
        ]);
        $this->assertNotNull(Transaction::find($transaction->id)->refunded_at);

        // Escrow transitioned to Refunded
        $this->assertDatabaseHas('escrow_holds', [
            'id'     => $hold->id,
            'status' => EscrowStatus::Refunded->value,
        ]);

        // Booking cancelled
        $this->assertDatabaseHas('booking_requests', [
            'id'     => $booking->id,
            'status' => BookingStatus::Cancelled->value,
        ]);
    }

    // ── AC2: Partial refund ───────────────────────────────────────────────────

    public function test_admin_can_process_partial_refund(): void
    {
        [$booking, $transaction] = $this->createPaidBookingWithTransaction(withEscrow: false);
        $this->fakePaystackRefundSuccess();

        $admin = $this->makeAdmin();
        $partialAmount = (int) ($booking->total_amount / 2);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson($this->refundUrl($booking->id), [
                'amount' => $partialAmount,
                'reason' => 'Remboursement partiel.',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('transactions', [
            'id'            => $transaction->id,
            'status'        => TransactionStatus::Refunded->value,
            'refund_amount' => $partialAmount,
        ]);
    }

    // ── AC3: Validation errors ────────────────────────────────────────────────

    public function test_returns_422_when_amount_is_zero(): void
    {
        [$booking] = $this->createPaidBookingWithTransaction();
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson($this->refundUrl($booking->id), [
                'amount' => 0,
                'reason' => 'Litige.',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    public function test_returns_422_when_reason_is_missing(): void
    {
        [$booking] = $this->createPaidBookingWithTransaction();
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson($this->refundUrl($booking->id), [
                'amount' => 1000,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    // ── AC4: Business rule errors ─────────────────────────────────────────────

    public function test_returns_422_when_refund_amount_exceeds_transaction(): void
    {
        [$booking, $transaction] = $this->createPaidBookingWithTransaction();
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson($this->refundUrl($booking->id), [
                'amount' => $booking->total_amount + 1,
                'reason' => 'Trop élevé.',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'REFUND_AMOUNT_EXCEEDS_TRANSACTION');

        // Transaction status NOT changed
        $this->assertDatabaseHas('transactions', [
            'id'     => $transaction->id,
            'status' => TransactionStatus::Succeeded->value,
        ]);
    }

    public function test_returns_422_when_no_succeeded_transaction_exists(): void
    {
        $booking = BookingRequest::factory()->paid()->create();
        // No transaction created
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson($this->refundUrl($booking->id), [
                'amount' => 1000,
                'reason' => 'Litige.',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'REFUND_NO_SUCCEEDED_TRANSACTION');
    }

    // ── AC5: Gateway failure — status rolls back ──────────────────────────────

    public function test_transaction_status_is_reset_to_succeeded_on_gateway_failure(): void
    {
        [$booking, $transaction] = $this->createPaidBookingWithTransaction();
        $this->fakePaystackRefundFailure();

        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson($this->refundUrl($booking->id), [
                'amount' => $booking->total_amount,
                'reason' => 'Litige.',
            ]);

        // PaymentException::gatewayError() maps to HTTP 502
        $response->assertStatus(502);

        // Status must be rolled back to Succeeded (no permanent state corruption)
        $this->assertDatabaseHas('transactions', [
            'id'            => $transaction->id,
            'status'        => TransactionStatus::Succeeded->value,
            'refund_amount' => null,
            'refund_reason' => null,
        ]);

        // Booking still in Paid status (not cancelled)
        $this->assertDatabaseHas('booking_requests', [
            'id'     => $booking->id,
            'status' => BookingStatus::Paid->value,
        ]);
    }

    // ── AC6: Access control ───────────────────────────────────────────────────

    public function test_returns_403_when_non_admin_user_attempts_refund(): void
    {
        [$booking] = $this->createPaidBookingWithTransaction();
        $client = $this->makeClient();

        $response = $this->actingAs($client, 'sanctum')
            ->postJson($this->refundUrl($booking->id), [
                'amount' => 1000,
                'reason' => 'Litige.',
            ]);

        $response->assertStatus(403);
    }

    public function test_returns_401_when_unauthenticated(): void
    {
        [$booking] = $this->createPaidBookingWithTransaction();

        $response = $this->postJson($this->refundUrl($booking->id), [
            'amount' => 1000,
            'reason' => 'Litige.',
        ]);

        $response->assertStatus(401);
    }

    public function test_returns_404_for_unknown_booking(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson($this->refundUrl(99999), [
                'amount' => 1000,
                'reason' => 'Litige.',
            ]);

        $response->assertStatus(404);
    }
}
