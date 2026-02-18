<?php

namespace Tests\Feature\Api\V1;

use App\Enums\TransactionStatus;
use App\Models\BookingRequest;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentCardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([ThrottleRequests::class, ThrottleRequestsWithRedis::class]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function paystackInitializeSuccess(string $reference = 'pstk_card_ref_001'): array
    {
        return [
            'status'  => true,
            'message' => 'Authorization URL created',
            'data'    => [
                'authorization_url' => 'https://checkout.paystack.com/test_auth_code',
                'access_code'       => 'test_access_code_001',
                'reference'         => $reference,
            ],
        ];
    }

    private function paystackInitializeFailure(): array
    {
        return ['status' => false, 'message' => 'Card declined'];
    }

    // ── AC1: paiement carte bancaire ──────────────────────────────────────

    public function test_client_can_initiate_card_payment_and_receives_authorization_url(): void
    {
        Http::fake([
            'https://api.paystack.co/transaction/initialize' => Http::response(
                $this->paystackInitializeSuccess(),
                200
            ),
        ]);

        $client  = User::factory()->create();
        $booking = BookingRequest::factory()->accepted()->create(['client_id' => $client->id]);

        $response = $this->actingAs($client, 'sanctum')
            ->postJson('/api/v1/payments/initiate', [
                'booking_id'     => $booking->id,
                'payment_method' => 'card',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'processing')
            ->assertJsonPath('payment_method', 'card')
            ->assertJsonPath('currency', 'XOF')
            ->assertJsonPath('gateway', 'paystack')
            ->assertJsonStructure(['authorization_url', 'access_code', 'gateway_reference']);

        $this->assertDatabaseHas('transactions', [
            'booking_request_id' => $booking->id,
            'payment_method'     => 'card',
            'status'             => TransactionStatus::Processing->value,
        ]);
    }

    public function test_client_can_initiate_bank_transfer_payment(): void
    {
        Http::fake([
            'https://api.paystack.co/transaction/initialize' => Http::response(
                $this->paystackInitializeSuccess('pstk_bt_ref_001'),
                200
            ),
        ]);

        $client  = User::factory()->create();
        $booking = BookingRequest::factory()->accepted()->create(['client_id' => $client->id]);

        $response = $this->actingAs($client, 'sanctum')
            ->postJson('/api/v1/payments/initiate', [
                'booking_id'     => $booking->id,
                'payment_method' => 'bank_transfer',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('payment_method', 'bank_transfer')
            ->assertJsonStructure(['authorization_url']);
    }

    public function test_card_payment_does_not_require_phone_number(): void
    {
        Http::fake([
            'https://api.paystack.co/transaction/initialize' => Http::response(
                $this->paystackInitializeSuccess(),
                200
            ),
        ]);

        $client  = User::factory()->create();
        $booking = BookingRequest::factory()->accepted()->create(['client_id' => $client->id]);

        $response = $this->actingAs($client, 'sanctum')
            ->postJson('/api/v1/payments/initiate', [
                'booking_id'     => $booking->id,
                'payment_method' => 'card',
                // phone_number intentionally absent
            ]);

        $response->assertStatus(201);
    }

    public function test_mobile_money_still_requires_phone_number(): void
    {
        Http::preventStrayRequests();

        $client  = User::factory()->create();
        $booking = BookingRequest::factory()->accepted()->create(['client_id' => $client->id]);

        $response = $this->actingAs($client, 'sanctum')
            ->postJson('/api/v1/payments/initiate', [
                'booking_id'     => $booking->id,
                'payment_method' => 'orange_money',
                // phone_number absent → validation error
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure(['error' => ['details' => ['errors' => ['phone_number']]]]);

        Http::assertNothingSent();
    }

    public function test_card_payment_returns_502_when_paystack_fails(): void
    {
        Http::fake([
            'https://api.paystack.co/transaction/initialize' => Http::response(
                $this->paystackInitializeFailure(),
                400
            ),
        ]);

        $client  = User::factory()->create();
        $booking = BookingRequest::factory()->accepted()->create(['client_id' => $client->id]);

        $response = $this->actingAs($client, 'sanctum')
            ->postJson('/api/v1/payments/initiate', [
                'booking_id'     => $booking->id,
                'payment_method' => 'card',
            ]);

        $response->assertStatus(502)
            ->assertJsonPath('error.code', 'PAYMENT_GATEWAY_ERROR');

        $this->assertDatabaseHas('transactions', [
            'booking_request_id' => $booking->id,
            'status'             => TransactionStatus::Failed->value,
        ]);
    }

    // ── AC2: callback URL ──────────────────────────────────────────────────

    public function test_callback_returns_200_with_known_reference(): void
    {
        $client  = User::factory()->create();
        $booking = BookingRequest::factory()->accepted()->create(['client_id' => $client->id]);

        Transaction::create([
            'booking_request_id' => $booking->id,
            'payment_method'     => 'card',
            'amount'             => $booking->total_amount,
            'currency'           => 'XOF',
            'gateway'            => 'paystack',
            'status'             => TransactionStatus::Processing->value,
            'idempotency_key'    => 'callback-known-ref',
            'initiated_at'       => now(),
        ]);

        $response = $this->getJson('/api/v1/payments/callback?reference=callback-known-ref');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'received')
            ->assertJsonPath('reference', 'callback-known-ref');
    }

    public function test_callback_returns_400_when_reference_missing(): void
    {
        $response = $this->getJson('/api/v1/payments/callback');

        $response->assertStatus(400)
            ->assertJsonPath('code', 'PAYMENT_CALLBACK_MISSING_REFERENCE');
    }

    public function test_callback_returns_404_for_unknown_reference(): void
    {
        $response = $this->getJson('/api/v1/payments/callback?reference=non-existent-ref');

        $response->assertStatus(404)
            ->assertJsonPath('code', 'PAYMENT_CALLBACK_UNKNOWN_REFERENCE');
    }

    public function test_callback_is_accessible_without_authentication(): void
    {
        $client  = User::factory()->create();
        $booking = BookingRequest::factory()->accepted()->create(['client_id' => $client->id]);

        Transaction::create([
            'booking_request_id' => $booking->id,
            'payment_method'     => 'card',
            'amount'             => $booking->total_amount,
            'currency'           => 'XOF',
            'gateway'            => 'paystack',
            'status'             => TransactionStatus::Processing->value,
            'idempotency_key'    => 'public-callback-test-ref',
            'initiated_at'       => now(),
        ]);

        $response = $this->getJson('/api/v1/payments/callback?reference=public-callback-test-ref');

        // Must be accessible without auth (Flutter WebView, not logged-in browser)
        $response->assertStatus(200);
    }

    // ── AC3: GET /payments/{transaction}/status ────────────────────────────

    public function test_client_can_poll_transaction_status(): void
    {
        Http::preventStrayRequests();

        $client  = User::factory()->create();
        $booking = BookingRequest::factory()->accepted()->create(['client_id' => $client->id]);

        $transaction = Transaction::create([
            'booking_request_id' => $booking->id,
            'payment_method'     => 'card',
            'amount'             => $booking->total_amount,
            'currency'           => 'XOF',
            'gateway'            => 'paystack',
            'status'             => TransactionStatus::Processing->value,
            'idempotency_key'    => 'poll-test-key',
            'initiated_at'       => now(),
        ]);

        $response = $this->actingAs($client, 'sanctum')
            ->getJson("/api/v1/payments/{$transaction->id}/status");

        $response->assertStatus(200)
            ->assertJsonPath('status', TransactionStatus::Processing->value)
            ->assertJsonPath('payment_method', 'card')
            ->assertJsonStructure(['id', 'booking_id', 'status', 'gateway', 'authorization_url']);
    }

    public function test_status_returns_403_for_non_owner(): void
    {
        Http::preventStrayRequests();

        $client    = User::factory()->create();
        $otherUser = User::factory()->create();
        $booking   = BookingRequest::factory()->accepted()->create(['client_id' => $client->id]);

        $transaction = Transaction::create([
            'booking_request_id' => $booking->id,
            'payment_method'     => 'card',
            'amount'             => $booking->total_amount,
            'currency'           => 'XOF',
            'gateway'            => 'paystack',
            'status'             => TransactionStatus::Processing->value,
            'idempotency_key'    => 'ownership-status-key',
            'initiated_at'       => now(),
        ]);

        $response = $this->actingAs($otherUser, 'sanctum')
            ->getJson("/api/v1/payments/{$transaction->id}/status");

        $response->assertStatus(403);
    }

    public function test_status_returns_404_for_unknown_transaction(): void
    {
        Http::preventStrayRequests();

        $client = User::factory()->create();

        $response = $this->actingAs($client, 'sanctum')
            ->getJson('/api/v1/payments/99999/status');

        $response->assertStatus(404);
    }

    public function test_status_returns_401_when_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/payments/1/status');

        $response->assertStatus(401);
    }
}
