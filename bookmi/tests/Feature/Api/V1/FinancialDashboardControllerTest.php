<?php

namespace Tests\Feature\Api\V1;

use App\Enums\EscrowStatus;
use App\Enums\TransactionStatus;
use App\Models\BookingRequest;
use App\Models\EscrowHold;
use App\Models\Payout;
use App\Models\TalentProfile;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Tests\TestCase;

class FinancialDashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([ThrottleRequests::class, ThrottleRequestsWithRedis::class]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeTalentUser(): array
    {
        $user    = User::factory()->create();
        $profile = TalentProfile::factory()->create(['user_id' => $user->id]);

        return [$user, $profile];
    }

    /**
     * Create a minimal EscrowHold (requires Transaction → BookingRequest).
     */
    private function createEscrowHold(TalentProfile $talentProfile, int $cachetAmount): EscrowHold
    {
        $booking = BookingRequest::factory()->confirmed()->create([
            'talent_profile_id' => $talentProfile->id,
            'cachet_amount'     => $cachetAmount,
            'commission_amount' => (int) round($cachetAmount * 0.15),
            'total_amount'      => $cachetAmount + (int) round($cachetAmount * 0.15),
        ]);

        $transaction = Transaction::create([
            'booking_request_id' => $booking->id,
            'payment_method'     => 'card',
            'amount'             => $booking->total_amount,
            'currency'           => 'XOF',
            'gateway'            => 'paystack',
            'status'             => TransactionStatus::Succeeded->value,
            'idempotency_key'    => 'fin-test-' . uniqid(),
            'initiated_at'       => now(),
        ]);

        return EscrowHold::create([
            'transaction_id'       => $transaction->id,
            'booking_request_id'   => $booking->id,
            'cachet_amount'        => $booking->cachet_amount,
            'commission_amount'    => $booking->commission_amount,
            'total_amount'         => $booking->total_amount,
            'status'               => EscrowStatus::Released->value,
            'held_at'              => now()->subHours(49),
            'release_scheduled_at' => now()->subHours(1),
            'released_at'          => now(),
        ]);
    }

    private function createPayout(
        TalentProfile $profile,
        int $amount,
        string $status = 'succeeded',
        ?\DateTimeInterface $processedAt = null,
    ): Payout {
        $hold = $this->createEscrowHold($profile, $amount);

        return Payout::create([
            'talent_profile_id' => $profile->id,
            'escrow_hold_id'    => $hold->id,
            'amount'            => $amount,
            'payout_method'     => 'orange_money',
            'payout_details'    => ['phone' => '+22500000000'],
            'gateway'           => 'paystack',
            'status'            => $status,
            'processed_at'      => $processedAt ?? now(),
        ]);
    }

    // ── GET /me/financial_dashboard ───────────────────────────────────────────

    public function test_talent_can_get_financial_dashboard_with_correct_totals(): void
    {
        [$user, $profile] = $this->makeTalentUser();

        // 2 succeeded payouts this month
        $this->createPayout($profile, 5_000_000, 'succeeded', now()->startOfMonth()->addDays(2));
        $this->createPayout($profile, 3_000_000, 'succeeded', now()->startOfMonth()->addDays(5));
        // 1 succeeded payout last month
        $this->createPayout($profile, 2_000_000, 'succeeded', now()->subMonth()->startOfMonth()->addDays(10));
        // 1 failed payout — must NOT be counted
        $this->createPayout($profile, 1_000_000, 'failed', now()->subDays(3));

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/financial_dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('data.revenus_total', 10_000_000)   // succeeded only: 5+3+2
            ->assertJsonPath('data.revenus_mois_courant', 8_000_000)  // 5+3
            ->assertJsonPath('data.revenus_mois_precedent', 2_000_000)
            ->assertJsonPath('data.nombre_prestations', 3)
            ->assertJsonPath('data.devise', 'XOF')
            ->assertJsonStructure([
                'data' => [
                    'revenus_total',
                    'revenus_mois_courant',
                    'revenus_mois_precedent',
                    'comparaison_pourcentage',
                    'nombre_prestations',
                    'cachet_moyen',
                    'devise',
                    'mensuels',
                ],
            ]);

        // Comparison: (8M - 2M) / 2M × 100 = 300%
        $this->assertEquals(300.0, $response->json('data.comparaison_pourcentage'));
        // Cachet moyen: 10M / 3 ≈ 3_333_333
        $this->assertEquals(3_333_333, $response->json('data.cachet_moyen'));
    }

    public function test_mensuels_contains_exactly_6_entries(): void
    {
        [$user] = $this->makeTalentUser();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/financial_dashboard');

        $response->assertStatus(200);
        $mensuels = $response->json('data.mensuels');
        $this->assertCount(6, $mensuels);
        $this->assertArrayHasKey('mois', $mensuels[0]);
        $this->assertArrayHasKey('revenus', $mensuels[0]);
    }

    public function test_dashboard_returns_zeros_when_no_payouts(): void
    {
        [$user] = $this->makeTalentUser();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/financial_dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('data.revenus_total', 0)
            ->assertJsonPath('data.nombre_prestations', 0)
            ->assertJsonPath('data.cachet_moyen', 0)
            ->assertJsonPath('data.comparaison_pourcentage', 0); // PHP serializes 0.0 as 0 in JSON
    }

    public function test_comparaison_is_100_when_previous_month_was_zero(): void
    {
        [$user, $profile] = $this->makeTalentUser();
        $this->createPayout($profile, 5_000_000, 'succeeded', now()->startOfMonth()->addDays(1));

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/financial_dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('data.comparaison_pourcentage', 100); // PHP serializes 100.0 as 100 in JSON
    }

    public function test_dashboard_returns_404_if_user_has_no_talent_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/financial_dashboard');

        $response->assertStatus(404)
            ->assertJsonPath('error.code', 'TALENT_PROFILE_NOT_FOUND');
    }

    public function test_dashboard_returns_401_if_unauthenticated(): void
    {
        $this->getJson('/api/v1/me/financial_dashboard')->assertStatus(401);
    }

    // ── GET /me/payouts ───────────────────────────────────────────────────────

    public function test_talent_can_get_paginated_payout_history(): void
    {
        [$user, $profile] = $this->makeTalentUser();

        $this->createPayout($profile, 5_000_000);
        $this->createPayout($profile, 3_000_000);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/payouts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $this->assertCount(2, $response->json('data'));
        $this->assertEquals(2, $response->json('meta.total'));
    }

    public function test_payouts_does_not_include_other_talents(): void
    {
        [$user, $profile] = $this->makeTalentUser();
        [, $otherProfile] = $this->makeTalentUser();

        $this->createPayout($profile, 5_000_000);
        $this->createPayout($otherProfile, 3_000_000); // other talent

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/payouts');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_payouts_returns_404_if_user_has_no_talent_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/payouts')
            ->assertStatus(404);
    }

    public function test_payouts_returns_401_if_unauthenticated(): void
    {
        $this->getJson('/api/v1/me/payouts')->assertStatus(401);
    }

    // ── Calendar alerts ───────────────────────────────────────────────────────

    public function test_calendar_alerts_returns_alert_structure(): void
    {
        [$user, $profile] = $this->makeTalentUser();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/calendar/alerts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'is_overloaded',
                    'active_booking_count',
                    'overload_threshold',
                    'has_empty_upcoming',
                ],
            ]);
    }

    public function test_calendar_alerts_detects_overload(): void
    {
        [$user, $profile] = $this->makeTalentUser();
        $profile->update(['overload_threshold' => 2]);

        // Create 3 active bookings (paid)
        BookingRequest::factory()->count(3)->create([
            'talent_profile_id' => $profile->id,
            'status'            => 'paid',
            'event_date'        => now()->addDays(10)->toDateString(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/calendar/alerts');

        $response->assertStatus(200)
            ->assertJson(['data' => ['is_overloaded' => true, 'active_booking_count' => 3]]);
    }

    public function test_calendar_alerts_detects_empty_upcoming(): void
    {
        [$user, $profile] = $this->makeTalentUser();

        // No upcoming bookings — should report empty
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/calendar/alerts');

        $response->assertStatus(200)
            ->assertJson(['data' => ['has_empty_upcoming' => true]]);
    }

    public function test_calendar_alerts_returns_404_for_non_talent(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/calendar/alerts')
            ->assertStatus(404);
    }

    // ── Earnings export ───────────────────────────────────────────────────────

    public function test_export_earnings_returns_csv_for_talent(): void
    {
        [$user, $profile] = $this->makeTalentUser();

        BookingRequest::factory()->create([
            'talent_profile_id' => $profile->id,
            'status'            => 'completed',
            'cachet_amount'     => 100_000,
            'commission_amount' => 15_000,
            'event_date'        => now()->subDays(5)->toDateString(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->get('/api/v1/me/earnings/export');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type') ?? '');
    }

    public function test_export_earnings_returns_404_for_non_talent(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->get('/api/v1/me/earnings/export')
            ->assertStatus(404);
    }
}
