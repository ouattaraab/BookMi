<?php

namespace Tests\Feature\Gateways;

use App\Enums\TransactionStatus;
use App\Models\BookingRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PaymentGatewayResolverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([ThrottleRequests::class, ThrottleRequestsWithRedis::class]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function paystackChargeSuccess(string $reference = 'pstk_ref_001'): array
    {
        return [
            'status'  => true,
            'message' => 'Charge attempted',
            'data'    => ['status' => 'send_otp', 'reference' => $reference, 'display_text' => 'Enter OTP'],
        ];
    }

    private function fedapayTransactionSuccess(): array
    {
        return [
            'v_transaction' => ['id' => 12345, 'status' => 'pending', 'description' => 'BookMi payment'],
        ];
    }

    private function fedapayPaySuccess(): array
    {
        return [
            'v_transaction' => ['id' => 12345, 'status' => 'pending'],
        ];
    }

    // ── AC1: primary succeeds ─────────────────────────────────────────────────

    public function test_primary_gateway_used_when_available(): void
    {
        Http::fake([
            'https://api.paystack.co/charge' => Http::response($this->paystackChargeSuccess(), 200),
        ]);

        $client  = User::factory()->create();
        $booking = BookingRequest::factory()->accepted()->create(['client_id' => $client->id]);

        $response = $this->actingAs($client, 'sanctum')
            ->postJson('/api/v1/payments/initiate', [
                'booking_id'     => $booking->id,
                'payment_method' => 'orange_money',
                'phone_number'   => '+22601234567',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('gateway', 'paystack');
    }

    // ── AC2: primary fails → fallback to FedaPay ─────────────────────────────

    public function test_fallback_to_fedapay_when_paystack_charge_fails(): void
    {
        Http::fake([
            'https://api.paystack.co/charge'             => Http::response(['status' => false, 'message' => 'Service unavailable'], 503),
            'https://api.fedapay.com/v1/transactions'    => Http::response($this->fedapayTransactionSuccess(), 200),
            'https://api.fedapay.com/v1/transactions/12345/pay' => Http::response($this->fedapayPaySuccess(), 200),
        ]);

        Log::shouldReceive('info')->withAnyArgs()->zeroOrMoreTimes();
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn (string $msg) => str_contains($msg, 'paystack') && str_contains($msg, 'fallback'));

        $client  = User::factory()->create();
        $booking = BookingRequest::factory()->accepted()->create(['client_id' => $client->id]);

        $response = $this->actingAs($client, 'sanctum')
            ->postJson('/api/v1/payments/initiate', [
                'booking_id'     => $booking->id,
                'payment_method' => 'orange_money',
                'phone_number'   => '+22601234567',
            ]);

        $response->assertStatus(201);
    }

    // ── AC3: both fail → 502 ──────────────────────────────────────────────────

    public function test_returns_502_when_both_gateways_fail(): void
    {
        Http::fake([
            'https://api.paystack.co/charge'          => Http::response(['status' => false, 'message' => 'Down'], 503),
            'https://api.fedapay.com/v1/transactions' => Http::response(['error' => 'Service unavailable'], 500),
        ]);

        $client  = User::factory()->create();
        $booking = BookingRequest::factory()->accepted()->create(['client_id' => $client->id]);

        $response = $this->actingAs($client, 'sanctum')
            ->postJson('/api/v1/payments/initiate', [
                'booking_id'     => $booking->id,
                'payment_method' => 'orange_money',
                'phone_number'   => '+22601234567',
            ]);

        $response->assertStatus(502)
            ->assertJsonPath('error.code', 'PAYMENT_GATEWAY_ERROR');
    }

    // ── AC4: card — FedaPay fallback ──────────────────────────────────────────

    public function test_card_payment_falls_back_to_fedapay_when_paystack_fails(): void
    {
        Http::fake([
            'https://api.paystack.co/transaction/initialize'   => Http::response(['status' => false, 'message' => 'Unavailable'], 503),
            'https://api.fedapay.com/v1/transactions'          => Http::response($this->fedapayTransactionSuccess(), 200),
            'https://api.fedapay.com/v1/transactions/12345/token' => Http::response(['token' => 'test_token_abc'], 200),
        ]);

        $client  = User::factory()->create();
        $booking = BookingRequest::factory()->accepted()->create(['client_id' => $client->id]);

        $response = $this->actingAs($client, 'sanctum')
            ->postJson('/api/v1/payments/initiate', [
                'booking_id'     => $booking->id,
                'payment_method' => 'card',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['authorization_url']);
    }

    // ── AC5: submitOtp never falls back ───────────────────────────────────────

    public function test_submit_otp_does_not_fall_back_to_fedapay(): void
    {
        Http::fake([
            'https://api.paystack.co/charge/submit_otp' => Http::response(['status' => false, 'message' => 'Unavailable'], 503),
        ]);

        $client  = User::factory()->create();
        $booking = BookingRequest::factory()->accepted()->create(['client_id' => $client->id]);

        \App\Models\Transaction::create([
            'booking_request_id' => $booking->id,
            'payment_method'     => 'orange_money',
            'amount'             => $booking->total_amount,
            'currency'           => 'XOF',
            'gateway'            => 'paystack',
            'status'             => TransactionStatus::Processing->value,
            'idempotency_key'    => 'otp-no-fallback-key',
            'initiated_at'       => now(),
        ]);

        $response = $this->actingAs($client, 'sanctum')
            ->postJson('/api/v1/payments/submit_otp', [
                'reference' => 'otp-no-fallback-key',
                'otp'       => '123456',
            ]);

        // Must be 502 (gateway error) — FedaPay NOT called
        $response->assertStatus(502);
        Http::assertSentCount(1); // Only Paystack
    }
}
