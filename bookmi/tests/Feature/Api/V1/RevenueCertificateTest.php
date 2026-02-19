<?php

namespace Tests\Feature\Api\V1;

use App\Enums\BookingStatus;
use App\Models\BookingRequest;
use App\Models\TalentProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RevenueCertificateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function createTalentWithProfile(): array
    {
        $user = User::factory()->create();
        $user->assignRole('talent');
        $talent = TalentProfile::factory()->create(['user_id' => $user->id]);

        return [$user, $talent];
    }

    // ─────────────────────────────────────────────
    // Story 7.9 — Attestation de revenus
    // ─────────────────────────────────────────────

    #[Test]
    public function talent_can_download_revenue_certificate_as_pdf(): void
    {
        [$user, $talent] = $this->createTalentWithProfile();

        $client = User::factory()->create();
        BookingRequest::factory()->create([
            'talent_profile_id' => $talent->id,
            'client_id' => $client->id,
            'status' => BookingStatus::Completed->value,
            'event_date' => '2025-06-15',
            'cachet_amount' => 50000,
            'commission_amount' => 7500,
            'total_amount' => 42500,
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->get('/api/v1/me/revenue_certificate?year=2025');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');
    }

    #[Test]
    public function revenue_certificate_requires_year_parameter(): void
    {
        [$user] = $this->createTalentWithProfile();

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/v1/me/revenue_certificate');

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    #[Test]
    public function revenue_certificate_returns_404_if_no_talent_profile(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client, 'sanctum');

        $response = $this->getJson('/api/v1/me/revenue_certificate?year=2025');

        $response->assertStatus(404)
            ->assertJsonPath('error.code', 'TALENT_PROFILE_NOT_FOUND');
    }

    #[Test]
    public function revenue_certificate_works_with_no_bookings_in_year(): void
    {
        [$user] = $this->createTalentWithProfile();

        $this->actingAs($user, 'sanctum');

        $response = $this->get('/api/v1/me/revenue_certificate?year=2020');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');
    }
}
