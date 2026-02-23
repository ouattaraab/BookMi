<?php

namespace Tests\Feature\Api\V1;

use App\Enums\TransactionStatus;
use App\Models\BookingRequest;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Tests\TestCase;

class AdminReportControllerTest extends TestCase
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

    private function createSucceededTransaction(): Transaction
    {
        $booking = BookingRequest::factory()->paid()->create();

        return Transaction::create([
            'booking_request_id' => $booking->id,
            'payment_method'     => 'orange_money',
            'amount'             => 11_500_000,
            'currency'           => 'XOF',
            'gateway'            => 'paystack',
            'gateway_reference'  => 'pstk_ref_' . uniqid(),
            'status'             => TransactionStatus::Succeeded->value,
            'idempotency_key'    => 'test-key-' . uniqid(),
            'initiated_at'       => now()->subDays(5),
        ]);
    }

    // ── GET /api/v1/admin/reports/financial ───────────────────────────────────

    public function test_admin_can_download_csv_report(): void
    {
        $this->createSucceededTransaction();

        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin, 'sanctum')
            ->get('/api/v1/admin/reports/financial?start_date=2020-01-01&end_date=2035-12-31');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_csv_contains_transactions_section(): void
    {
        $this->createSucceededTransaction();
        $admin = $this->makeAdmin();

        $content = $this->actingAs($admin, 'sanctum')
            ->get('/api/v1/admin/reports/financial?start_date=2020-01-01&end_date=2035-12-31')
            ->streamedContent();

        $this->assertStringContainsString('TRANSACTIONS', $content);
        $this->assertStringContainsString('VERSEMENTS', $content);
        $this->assertStringContainsString('REMBOURSEMENTS', $content);
    }

    public function test_csv_includes_refunded_transactions(): void
    {
        $booking     = BookingRequest::factory()->cancelled()->create();
        $transaction = Transaction::create([
            'booking_request_id' => $booking->id,
            'payment_method'     => 'card',
            'amount'             => 5_000_000,
            'currency'           => 'XOF',
            'gateway'            => 'paystack',
            'status'             => TransactionStatus::Refunded->value,
            'idempotency_key'    => 'refund-test-' . uniqid(),
            'initiated_at'       => now()->subDays(3),
            'refund_amount'      => 5_000_000,
            'refund_reference'   => 'rfnd_xyz',
            'refund_reason'      => 'Litige client',
            'refunded_at'        => now()->subDays(2),
        ]);

        $admin = $this->makeAdmin();

        $content = $this->actingAs($admin, 'sanctum')
            ->get('/api/v1/admin/reports/financial?start_date=2020-01-01&end_date=2035-12-31')
            ->streamedContent();

        $this->assertStringContainsString('rfnd_xyz', $content);
        $this->assertStringContainsString('Litige client', $content);
    }

    public function test_returns_422_when_date_params_missing(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/reports/financial');

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    public function test_returns_422_when_end_date_before_start_date(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/reports/financial?start_date=2026-02-28&end_date=2026-01-01');

        $response->assertStatus(422);
    }

    public function test_returns_403_when_non_admin(): void
    {
        $client = $this->makeClient();

        $response = $this->actingAs($client, 'sanctum')
            ->get('/api/v1/admin/reports/financial?start_date=2026-01-01&end_date=2026-12-31');

        $response->assertStatus(403);
    }

    public function test_returns_401_when_unauthenticated(): void
    {
        // Use getJson to send Accept: application/json — prevents redirect to 'login' route
        $response = $this->getJson('/api/v1/admin/reports/financial?start_date=2026-01-01&end_date=2026-12-31');
        $response->assertStatus(401);
    }
}
