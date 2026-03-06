<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ConsentType;
use App\Models\User;
use App\Models\UserConsent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Tests\TestCase;

class ConsentControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, bool> */
    private array $baseConsents;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            ThrottleRequests::class,
            ThrottleRequestsWithRedis::class,
        ]);
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

        $this->baseConsents = [
            'cgu_privacy'             => true,
            'data_processing'         => true,
            'age_minimum'             => true,
            'surveillance_moderation' => true,
            'platform_communication'  => true,
            'dispute_resolution'      => true,
            'liability_disclaimer'    => true,
            'indemnification'         => true,
            'collective_waiver'       => true,
        ];
    }

    // ── Helper ─────────────────────────────────────────────────────────────

    /**
     * Build a valid client registration payload.
     *
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function clientPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name'            => 'Test',
            'last_name'             => 'Client',
            'email'                 => 'client@test.ci',
            'phone'                 => '+22507' . rand(10000000, 99999999),
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role'                  => 'client',
            'consents'              => array_merge($this->baseConsents, [
                'non_client_liability' => true,
                'cancellation_policy'  => true,
            ]),
        ], $overrides);
    }

    // ── Point 1 — Horodatage consentements à l'inscription ────────────────

    public function test_register_records_all_mandatory_consents_with_ip_and_timestamp(): void
    {
        $this->postJson('/api/v1/auth/register', $this->clientPayload())
            ->assertCreated();

        $user = User::where('email', 'client@test.ci')->firstOrFail();

        // All 9 base + 2 client-specific = 11 mandatory consents recorded
        $this->assertDatabaseCount('user_consents', 11);

        // CGU consent recorded with IP
        $consent = UserConsent::where('user_id', $user->id)
            ->where('consent_type', ConsentType::CguPrivacy->value)
            ->first();

        $this->assertNotNull($consent);
        $this->assertTrue($consent->status);
        $this->assertNotNull($consent->consented_at);
        $this->assertNotNull($consent->document_version);

        // CGU version accepted set on user
        $this->assertNotNull($user->fresh()->cgu_version_accepted);
    }

    // ── Point 2 — Inscription sans cgu_privacy → 422 ─────────────────────

    public function test_register_without_cgu_returns_422(): void
    {
        $payload          = $this->clientPayload();
        $payload['consents']['cgu_privacy'] = false;

        $this->postJson('/api/v1/auth/register', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    // ── Point 3 — Inscription sans data_processing → 422 ─────────────────

    public function test_register_without_data_processing_returns_422(): void
    {
        $payload = $this->clientPayload();
        unset($payload['consents']['data_processing']);

        $this->postJson('/api/v1/auth/register', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    // ── Point 4 — Inscription sans consentements → 422 ──────────────────

    public function test_register_without_consents_field_returns_422(): void
    {
        $payload = $this->clientPayload();
        unset($payload['consents']);

        $this->postJson('/api/v1/auth/register', $payload)
            ->assertUnprocessable();
    }

    // ── Point 5 — GET /consents retourne la liste ────────────────────────

    public function test_authenticated_user_can_fetch_consents(): void
    {
        $user = User::factory()->create(['cgu_version_accepted' => '2026-03-05']);
        $user->assignRole('client');

        UserConsent::create([
            'user_id'          => $user->id,
            'consent_type'     => ConsentType::CguPrivacy->value,
            'status'           => true,
            'document_version' => '2026-03-05',
            'consented_at'     => now(),
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/consents')
            ->assertOk()
            ->assertJsonPath('data.cgu_version_accepted', '2026-03-05')
            ->assertJsonStructure(['data' => ['consents', 'cgu_version_accepted', 'current_cgu_version']]);
    }

    // ── Point 6 — Mise à jour opt-in marketing (opt-out) ─────────────────

    public function test_opt_out_marketing_records_withdrawn_at(): void
    {
        $user = User::factory()->create([
            'marketing_opt_in'      => true,
            'cgu_version_accepted'  => '2026-03-05',
        ]);
        $user->assignRole('client');

        $this->actingAs($user)
            ->patchJson('/api/v1/consents/update', [
                'consents' => ['marketing' => false],
            ])
            ->assertOk();

        $this->assertDatabaseHas('user_consents', [
            'user_id'      => $user->id,
            'consent_type' => 'marketing',
            'status'       => false,
        ]);

        $consent = UserConsent::where('user_id', $user->id)
            ->where('consent_type', 'marketing')
            ->latest()
            ->first();

        $this->assertNotNull($consent?->withdrawn_at);
        $this->assertFalse($user->fresh()->marketing_opt_in);
    }

    // ── Point 7 — GET /consents accessible même si CGU obsolètes ───────────
    // Le middleware check.cgu a été retiré de GET /consents pour permettre
    // la lecture des consentements même si les CGU ne sont pas à jour.
    // Le middleware est toujours actif sur PATCH /consents/update.

    public function test_get_consents_accessible_even_when_cgu_outdated(): void
    {
        $user = User::factory()->create(['cgu_version_accepted' => '2020-01-01']);
        $user->assignRole('client');

        $this->actingAs($user)
            ->getJson('/api/v1/consents')
            ->assertOk()
            ->assertJsonStructure(['data' => ['consents', 'cgu_version_accepted', 'current_cgu_version']]);
    }

    public function test_update_consents_returns_403_when_cgu_outdated(): void
    {
        $user = User::factory()->create(['cgu_version_accepted' => '2020-01-01']);
        $user->assignRole('client');

        $this->actingAs($user)
            ->withMiddleware(\App\Http\Middleware\Api\CheckCguVersion::class)
            ->patchJson('/api/v1/consents/update', ['consents' => ['marketing' => true]])
            ->assertForbidden()
            ->assertJsonPath('requires_reconsent', true);
    }

    // ── Point 8 — POST /consents/reconsent met à jour cgu_version_accepted ─

    public function test_reconsent_updates_cgu_version_accepted(): void
    {
        $user = User::factory()->create(['cgu_version_accepted' => null]);
        $user->assignRole('client');

        $this->actingAs($user)
            ->postJson('/api/v1/consents/reconsent', [
                'consents' => [
                    'cgu_update'      => true,
                    'data_processing' => true,
                ],
            ])
            ->assertOk();

        $this->assertNotNull($user->fresh()->cgu_version_accepted);
        $this->assertDatabaseHas('user_consents', [
            'user_id'      => $user->id,
            'consent_type' => 'cgu_update',
            'status'       => true,
        ]);
    }

    // ── Point 9 — PATCH avec type non-opt-in → 422 ────────────────────────

    public function test_update_non_optin_consent_returns_422(): void
    {
        $user = User::factory()->create(['cgu_version_accepted' => '2026-03-05']);
        $user->assignRole('client');

        $this->actingAs($user)
            ->patchJson('/api/v1/consents/update', [
                'consents' => ['cgu_privacy' => false], // cgu_privacy n'est pas opt-in
            ])
            ->assertUnprocessable();
    }

    // ── Point 10 — Consentement transactionnel enregistré à la réservation ─

    public function test_transactional_consents_recorded_when_booking_created(): void
    {
        $client = User::factory()->create([
            'cgu_version_accepted' => '2026-03-05',
        ]);
        $client->assignRole('client');

        $talent = User::factory()->create();
        $talent->assignRole('talent');

        // Create minimal TalentProfile + ServicePackage via factories
        $talentProfile = \App\Models\TalentProfile::factory()->create([
            'user_id'     => $talent->id,
            'is_verified' => true,
        ]);
        $package = \App\Models\ServicePackage::factory()->create([
            'talent_profile_id' => $talentProfile->id,
            'type'              => 'standard',
        ]);

        $this->actingAs($client)
            ->postJson('/api/v1/booking_requests', [
                'talent_profile_id'  => $talentProfile->id,
                'service_package_id' => $package->id,
                'event_date'         => now()->addDays(10)->format('Y-m-d'),
                'start_time'         => '18:00',
                'event_location'     => 'Abidjan',
                'consents'           => [
                    'transaction_payment'      => true,
                    'transaction_cancellation' => true,
                ],
            ])
            ->assertCreated();

        $this->assertDatabaseHas('user_consents', [
            'user_id'      => $client->id,
            'consent_type' => 'transaction_payment',
            'status'       => true,
        ]);
        $this->assertDatabaseHas('user_consents', [
            'user_id'      => $client->id,
            'consent_type' => 'transaction_cancellation',
            'status'       => true,
        ]);
    }
}
