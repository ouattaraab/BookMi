<?php

namespace Tests\Feature\Api\V1;

use App\Jobs\HandlePaymentWebhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PaystackWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([ThrottleRequests::class, ThrottleRequestsWithRedis::class]);
        // Pas de secret par défaut → validation de signature ignorée
        config(['services.paystack.webhook_secret' => null]);
    }

    private function webhookPayload(string $event = 'charge.success'): array
    {
        return [
            'event' => $event,
            'data'  => ['reference' => 'pstk_ref_test_001', 'status' => 'success'],
        ];
    }

    // ── AC1: réception du webhook ──────────────────────────────────────────

    public function test_valid_webhook_dispatches_job_on_payments_queue(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/v1/webhooks/paystack', $this->webhookPayload());

        $response->assertStatus(200)
            ->assertJson(['status' => 'received']);

        Queue::assertPushed(HandlePaymentWebhook::class, function ($job) {
            return $job->event === 'charge.success';
        });
    }

    public function test_webhook_dispatches_job_for_charge_failed_event(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/v1/webhooks/paystack', $this->webhookPayload('charge.failed'));

        $response->assertStatus(200);
        Queue::assertPushed(HandlePaymentWebhook::class, fn ($job) => $job->event === 'charge.failed');
    }

    public function test_webhook_dispatches_job_even_for_unknown_events(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/v1/webhooks/paystack', [
            'event' => 'some.unknown.event',
            'data'  => ['reference' => 'ref_xyz'],
        ]);

        $response->assertStatus(200);
        Queue::assertPushed(HandlePaymentWebhook::class);
    }

    public function test_webhook_does_not_dispatch_when_event_is_missing(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/v1/webhooks/paystack', [
            'event' => '',
            'data'  => [],
        ]);

        $response->assertStatus(200);
        Queue::assertNotPushed(HandlePaymentWebhook::class);
    }

    // ── AC2: validation signature HMAC-SHA512 (NFR42) ────────────────────

    public function test_invalid_signature_returns_401_when_webhook_secret_is_configured(): void
    {
        config(['services.paystack.webhook_secret' => 'super_secret_key']);

        $response = $this->postJson(
            '/api/v1/webhooks/paystack',
            $this->webhookPayload(),
            ['x-paystack-signature' => 'deadbeef_invalid'],
        );

        $response->assertStatus(401)
            ->assertJsonPath('error.code', 'WEBHOOK_SIGNATURE_INVALID');
    }

    public function test_valid_hmac_signature_is_accepted_when_secret_is_configured(): void
    {
        Queue::fake();

        $secret  = 'super_secret_key';
        $payload = $this->webhookPayload();
        $body    = json_encode($payload);
        $sig     = hash_hmac('sha512', $body, $secret);

        config(['services.paystack.webhook_secret' => $secret]);

        $response = $this->call(
            'POST',
            '/api/v1/webhooks/paystack',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_PAYSTACK_SIGNATURE' => $sig],
            $body,
        );

        $response->assertStatus(200);
        Queue::assertPushed(HandlePaymentWebhook::class);
    }

    public function test_signature_check_skipped_when_no_webhook_secret_configured(): void
    {
        Queue::fake();

        // webhook_secret is null (configured in setUp)
        $response = $this->postJson(
            '/api/v1/webhooks/paystack',
            $this->webhookPayload(),
            ['x-paystack-signature' => 'any_random_value'],
        );

        $response->assertStatus(200);
        Queue::assertPushed(HandlePaymentWebhook::class);
    }
}
