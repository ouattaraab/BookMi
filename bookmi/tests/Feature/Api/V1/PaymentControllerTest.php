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

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([ThrottleRequests::class, ThrottleRequestsWithRedis::class]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function paystackChargeSuccess(string $reference = 'pstk_ref_001'): array
    {
        return [
            'status'  => true,
            'message' => 'Charge attempted',
            'data'    => [
                'reference'    => $reference,
                'status'       => 'send_otp',
                'display_text' => 'Veuillez saisir le code OTP envoyé sur votre téléphone',
                'amount'       => 1150000,
                'currency'     => 'XOF',
            ],
        ];
    }

    /** @return array<string, \Illuminate\Http\Client\Response> */
    private function fakePaystackSuccess(string $reference = 'pstk_ref_001'): array
    {
        return [
            'https://api.paystack.co/charge' => Http::response($this->paystackChargeSuccess($reference), 200),
        ];
    }

    /** @return array<string, \Illuminate\Http\Client\Response> */
    private function fakePaystackFailure(): array
    {
        return [
            'https://api.paystack.co/charge' => Http::response([
                'status'  => false,
                'message' => 'Provider unavailable',
            ], 400),
        ];
    }

    // ── AC1: initiate mobile money payment ────────────────────────────────

    public function test_client_can_initiate_orange_money_payment(): void
    {
        Http::fake($this->fakePaystackSuccess());

        $client  = User::factory()->create();
        $booking = BookingRequest::factory()->accepted()->create(['client_id' => $client->id]);

        $response = $this->actingAs($client, 'sanctum')
            ->postJson('/api/v1/payments/initiate', [
                'booking_id'     => $booking->id,
                'payment_method' => 'orange_money',
                'phone_number'   => '+2250700000000',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'processing')
            ->assertJsonPath('payment_method', 'orange_money')
            ->assertJsonPath('currency', 'XOF')
            ->assertJsonPath('gateway', 'paystack')
            ->assertJsonPath('gateway_status', 'send_otp')
            ->assertJsonStructure(['display_text', 'gateway_reference']);

        $this->assertDatabaseHas('transactions', [
            'booking_request_id' => $booking->id,
            'status'             => TransactionStatus::Processing->value,
            'payment_method'     => 'orange_money',
            'currency'           => 'XOF',
        ]);
    }

    public function test_wave_payment_method_is_accepted(): void
    {
        Http::fake($this->fakePaystackSuccess('pstk_wave_001'));

        $client  = User::factory()->create();
        $booking = BookingRequest::factory()->accepted()->create(['client_id' => $client->id]);

        $response = $this->actingAs($client, 'sanctum')
            ->postJson('/api/v1/payments/initiate', [
                'booking_id'     => $booking->id,
                'payment_method' => 'wave',
                'phone_number'   => '+2250700000001',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('payment_method', 'wave');
    }

    public function test_mtn_momo_payment_method_is_accepted(): void
    {
        Http::fake($this->fakePaystackSuccess('pstk_mtn_001'));

        $client  = User::factory()->create();
        $booking = BookingRequest::factory()->accepted()->create(['client_id' => $client->id]);

        $response = $this->actingAs($client, 'sanctum')
            ->postJson('/api/v1/payments/initiate', [
                'booking_id'     => $booking->id,
                'payment_method' => 'mtn_momo',
                'phone_number'   => '+2250700000002',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('payment_method', 'mtn_momo');
    }

    public function test_moov_money_payment_method_is_accepted(): void
    {
        Http::fake($this->fakePaystackSuccess('pstk_moov_001'));

        $client  = User::factory()->create();
        $booking = BookingRequest::factory()->accepted()->create(['client_id' => $client->id]);

        $response = $this->actingAs($client, 'sanctum')
            ->postJson('/api/v1/payments/initiate', [
                'booking_id'     => $booking->id,
                'payment_method' => 'moov_money',
                'phone_number'   => '+2250700000003',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('payment_method', 'moov_money');
    }

    // ── AC2: booking not in accepted status ───────────────────────────────

    public function test_returns_422_when_booking_is_not_accepted(): void
    {
        Http::preventStrayRequests();

        $client  = User::factory()->create();
        $booking = BookingRequest::factory()->pending()->create(['client_id' => $client->id]);

        $response = $this->actingAs($client, 'sanctum')
            ->postJson('/api/v1/payments/initiate', [
                'booking_id'     => $booking->id,
                'payment_method' => 'orange_money',
                'phone_number'   => '+2250700000000',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'PAYMENT_BOOKING_NOT_PAYABLE');

        Http::assertNothingSent();
    }

    // ── AC3: duplicate in-flight transaction ──────────────────────────────

    public function test_returns_409_when_transaction_already_in_progress(): void
    {
        Http::preventStrayRequests();

        $client  = User::factory()->create();
        $booking = BookingRequest::factory()->accepted()->create(['client_id' => $client->id]);

        Transaction::create([
            'booking_request_id' => $booking->id,
            'payment_method'     => 'orange_money',
            'amount'             => $booking->total_amount,
            'currency'           => 'XOF',
            'gateway'            => 'paystack',
            'status'             => TransactionStatus::Processing->value,
            'idempotency_key'    => 'existing-key-001',
            'initiated_at'       => now(),
        ]);

        $response = $this->actingAs($client, 'sanctum')
            ->postJson('/api/v1/payments/initiate', [
                'booking_id'     => $booking->id,
                'payment_method' => 'orange_money',
                'phone_number'   => '+2250700000000',
            ]);

        $response->assertStatus(409)
            ->assertJsonPath('error.code', 'PAYMENT_DUPLICATE');

        Http::assertNothingSent();
    }

    // ── AC4: authorization — only the client can pay ──────────────────────

    public function test_returns_403_when_non_client_tries_to_pay(): void
    {
        Http::preventStrayRequests();

        $client    = User::factory()->create();
        $otherUser = User::factory()->create();
        $booking   = BookingRequest::factory()->accepted()->create(['client_id' => $client->id]);

        $response = $this->actingAs($otherUser, 'sanctum')
            ->postJson('/api/v1/payments/initiate', [
                'booking_id'     => $booking->id,
                'payment_method' => 'orange_money',
                'phone_number'   => '+2250700000000',
            ]);

        $response->assertStatus(403);
        Http::assertNothingSent();
    }

    // ── AC5: unauthenticated request ──────────────────────────────────────

    public function test_returns_401_when_unauthenticated(): void
    {
        $response = $this->postJson('/api/v1/payments/initiate', [
            'booking_id'     => 1,
            'payment_method' => 'orange_money',
            'phone_number'   => '+2250700000000',
        ]);

        $response->assertStatus(401);
    }

    // ── AC6: validation ───────────────────────────────────────────────────

    public function test_returns_422_for_invalid_payment_method(): void
    {
        Http::preventStrayRequests();

        $client  = User::factory()->create();
        $booking = BookingRequest::factory()->accepted()->create(['client_id' => $client->id]);

        $response = $this->actingAs($client, 'sanctum')
            ->postJson('/api/v1/payments/initiate', [
                'booking_id'     => $booking->id,
                'payment_method' => 'bitcoin',
                'phone_number'   => '+2250700000000',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure(['error' => ['details' => ['errors' => ['payment_method']]]]);
    }

    public function test_returns_422_for_card_payment_method_on_mobile_money_endpoint(): void
    {
        Http::preventStrayRequests();

        $client  = User::factory()->create();
        $booking = BookingRequest::factory()->accepted()->create(['client_id' => $client->id]);

        // card is a valid PaymentMethod enum but excluded from mobile money endpoint
        $response = $this->actingAs($client, 'sanctum')
            ->postJson('/api/v1/payments/initiate', [
                'booking_id'     => $booking->id,
                'payment_method' => 'card',
                'phone_number'   => '+2250700000000',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    public function test_returns_422_for_invalid_phone_number(): void
    {
        Http::preventStrayRequests();

        $client  = User::factory()->create();
        $booking = BookingRequest::factory()->accepted()->create(['client_id' => $client->id]);

        $response = $this->actingAs($client, 'sanctum')
            ->postJson('/api/v1/payments/initiate', [
                'booking_id'     => $booking->id,
                'payment_method' => 'orange_money',
                'phone_number'   => 'not-a-phone',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure(['error' => ['details' => ['errors' => ['phone_number']]]]);
    }

    // ── AC7: gateway error → transaction marked failed + rollback ─────────

    public function test_returns_502_when_paystack_fails_and_no_processing_transaction_remains(): void
    {
        Http::fake($this->fakePaystackFailure());

        $client  = User::factory()->create();
        $booking = BookingRequest::factory()->accepted()->create(['client_id' => $client->id]);

        $response = $this->actingAs($client, 'sanctum')
            ->postJson('/api/v1/payments/initiate', [
                'booking_id'     => $booking->id,
                'payment_method' => 'orange_money',
                'phone_number'   => '+2250700000000',
            ]);

        $response->assertStatus(502)
            ->assertJsonPath('error.code', 'PAYMENT_GATEWAY_ERROR');

        // Transaction created but marked as failed (not processing) — gateway call is outside DB tx
        $this->assertDatabaseHas('transactions', [
            'booking_request_id' => $booking->id,
            'status'             => TransactionStatus::Failed->value,
        ]);

        // No transaction should be in processing state
        $this->assertDatabaseMissing('transactions', [
            'booking_request_id' => $booking->id,
            'status'             => TransactionStatus::Processing->value,
        ]);
    }

    // ── submit_otp ────────────────────────────────────────────────────────

    public function test_client_can_submit_otp_for_processing_transaction(): void
    {
        Http::fake([
            'https://api.paystack.co/charge/submit_otp' => Http::response([
                'status'  => true,
                'message' => 'OTP submitted',
                'data'    => [
                    'status'       => 'success',
                    'display_text' => 'Paiement effectué avec succès',
                ],
            ], 200),
        ]);

        $client  = User::factory()->create();
        $booking = BookingRequest::factory()->accepted()->create(['client_id' => $client->id]);

        $transaction = Transaction::create([
            'booking_request_id' => $booking->id,
            'payment_method'     => 'orange_money',
            'amount'             => $booking->total_amount,
            'currency'           => 'XOF',
            'gateway'            => 'paystack',
            'status'             => TransactionStatus::Processing->value,
            'idempotency_key'    => 'test-idem-key-submit-otp',
            'initiated_at'       => now(),
        ]);

        $response = $this->actingAs($client, 'sanctum')
            ->postJson('/api/v1/payments/submit_otp', [
                'reference' => $transaction->idempotency_key,
                'otp'       => '123456',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['status', 'display_text']);
    }

    public function test_submit_otp_returns_422_when_reference_does_not_exist(): void
    {
        Http::preventStrayRequests();

        $client = User::factory()->create();

        $response = $this->actingAs($client, 'sanctum')
            ->postJson('/api/v1/payments/submit_otp', [
                'reference' => 'non-existent-reference',
                'otp'       => '123456',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure(['error' => ['details' => ['errors' => ['reference']]]]);

        Http::assertNothingSent();
    }

    public function test_submit_otp_returns_422_when_otp_is_not_digits(): void
    {
        Http::preventStrayRequests();

        $client  = User::factory()->create();
        $booking = BookingRequest::factory()->accepted()->create(['client_id' => $client->id]);

        Transaction::create([
            'booking_request_id' => $booking->id,
            'payment_method'     => 'orange_money',
            'amount'             => $booking->total_amount,
            'currency'           => 'XOF',
            'gateway'            => 'paystack',
            'status'             => TransactionStatus::Processing->value,
            'idempotency_key'    => 'test-otp-validation-key',
            'initiated_at'       => now(),
        ]);

        $response = $this->actingAs($client, 'sanctum')
            ->postJson('/api/v1/payments/submit_otp', [
                'reference' => 'test-otp-validation-key',
                'otp'       => 'not-digits',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure(['error' => ['details' => ['errors' => ['otp']]]]);

        Http::assertNothingSent();
    }

    public function test_submit_otp_returns_422_when_transaction_not_in_processing_state(): void
    {
        Http::preventStrayRequests();

        $client  = User::factory()->create();
        $booking = BookingRequest::factory()->accepted()->create(['client_id' => $client->id]);

        // Transaction déjà en état succeeded
        Transaction::create([
            'booking_request_id' => $booking->id,
            'payment_method'     => 'orange_money',
            'amount'             => $booking->total_amount,
            'currency'           => 'XOF',
            'gateway'            => 'paystack',
            'status'             => TransactionStatus::Succeeded->value,
            'idempotency_key'    => 'test-succeeded-ref',
            'initiated_at'       => now(),
            'completed_at'       => now(),
        ]);

        $response = $this->actingAs($client, 'sanctum')
            ->postJson('/api/v1/payments/submit_otp', [
                'reference' => 'test-succeeded-ref',
                'otp'       => '123456',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'PAYMENT_TRANSACTION_NOT_PROCESSING');

        Http::assertNothingSent();
    }

    public function test_submit_otp_returns_403_when_user_is_not_owner(): void
    {
        Http::preventStrayRequests();

        $client    = User::factory()->create();
        $otherUser = User::factory()->create();
        $booking   = BookingRequest::factory()->accepted()->create(['client_id' => $client->id]);

        Transaction::create([
            'booking_request_id' => $booking->id,
            'payment_method'     => 'orange_money',
            'amount'             => $booking->total_amount,
            'currency'           => 'XOF',
            'gateway'            => 'paystack',
            'status'             => TransactionStatus::Processing->value,
            'idempotency_key'    => 'test-ownership-ref',
            'initiated_at'       => now(),
        ]);

        $response = $this->actingAs($otherUser, 'sanctum')
            ->postJson('/api/v1/payments/submit_otp', [
                'reference' => 'test-ownership-ref',
                'otp'       => '123456',
            ]);

        $response->assertStatus(403);
        Http::assertNothingSent();
    }

    public function test_submit_otp_returns_401_when_unauthenticated(): void
    {
        $response = $this->postJson('/api/v1/payments/submit_otp', [
            'reference' => 'any-reference',
            'otp'       => '123456',
        ]);

        $response->assertStatus(401);
    }
}
