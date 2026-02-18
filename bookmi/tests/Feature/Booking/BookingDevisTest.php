<?php

namespace Tests\Feature\Booking;

use App\Enums\BookingStatus;
use App\Models\BookingRequest;
use App\Models\ServicePackage;
use App\Models\TalentProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingDevisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function createClientUser(): User
    {
        $user = User::factory()->create([
            'phone_verified_at' => now(),
            'is_active'         => true,
        ]);
        $user->assignRole('client');

        return $user;
    }

    private function createTalentWithPackage(array $packageAttrs = []): array
    {
        $user = User::factory()->create([
            'phone_verified_at' => now(),
            'is_active'         => true,
        ]);
        $user->assignRole('talent');

        $talent  = TalentProfile::factory()->verified()->create(['user_id' => $user->id]);
        $package = ServicePackage::factory()->create(array_merge([
            'talent_profile_id' => $talent->id,
            'cachet_amount'     => 10_000_000,
            'is_active'         => true,
        ], $packageAttrs));

        return [$user, $talent, $package];
    }

    private function createBooking(
        User $client,
        TalentProfile $talent,
        ServicePackage $package,
        array $attrs = [],
    ): BookingRequest {
        return BookingRequest::create(array_merge([
            'client_id'          => $client->id,
            'talent_profile_id'  => $talent->id,
            'service_package_id' => $package->id,
            'event_date'         => now()->addDays(15)->format('Y-m-d'),
            'event_location'     => 'Cocody, Abidjan',
            'message'            => null,
            'status'             => BookingStatus::Pending,
            'cachet_amount'      => $package->cachet_amount,
            'commission_amount'  => (int) round($package->cachet_amount * 0.15),
            'total_amount'       => (int) round($package->cachet_amount * 1.15),
        ], $attrs));
    }

    // ─── AC1: Message de transparence dans le devis ───────────────────────────

    #[Test]
    public function show_response_includes_devis_transparency_message(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createBooking($client, $talent, $package);

        $this->actingAs($client, 'sanctum');

        $this->getJson("/api/v1/booking_requests/{$booking->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.devis.message', 'Cachet artiste intact — BookMi ajoute 15% de frais de service')
            ->assertJsonPath('data.devis.cachet_amount', 10_000_000)
            ->assertJsonPath('data.devis.commission_amount', 1_500_000)
            ->assertJsonPath('data.devis.total_amount', 11_500_000);
    }

    #[Test]
    public function list_response_includes_devis_transparency_message(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();
        $client = $this->createClientUser();
        $this->createBooking($client, $talent, $package);

        $this->actingAs($client, 'sanctum');

        $this->getJson('/api/v1/booking_requests')
            ->assertStatus(200)
            ->assertJsonPath('data.0.devis.message', 'Cachet artiste intact — BookMi ajoute 15% de frais de service');
    }

    // ─── AC2: Détail complet du package ──────────────────────────────────────

    #[Test]
    public function show_response_includes_full_package_details(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage([
            'description'      => 'Animation musicale 2h',
            'inclusions'       => ['Sono', 'Micro', 'Déplacement inclus'],
            'duration_minutes' => 120,
        ]);
        $client  = $this->createClientUser();
        $booking = $this->createBooking($client, $talent, $package);

        $this->actingAs($client, 'sanctum');

        $this->getJson("/api/v1/booking_requests/{$booking->id}")
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'service_package' => [
                        'id', 'name', 'type',
                        'description', 'inclusions', 'duration_minutes',
                    ],
                ],
            ])
            ->assertJsonPath('data.service_package.description', 'Animation musicale 2h')
            ->assertJsonPath('data.service_package.inclusions', ['Sono', 'Micro', 'Déplacement inclus'])
            ->assertJsonPath('data.service_package.duration_minutes', 120);
    }

    #[Test]
    public function show_response_handles_null_package_details(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage([
            'description'      => null,
            'inclusions'       => null,
            'duration_minutes' => null,
        ]);
        $client  = $this->createClientUser();
        $booking = $this->createBooking($client, $talent, $package);

        $this->actingAs($client, 'sanctum');

        $this->getJson("/api/v1/booking_requests/{$booking->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.service_package.description', null)
            ->assertJsonPath('data.service_package.inclusions', null)
            ->assertJsonPath('data.service_package.duration_minutes', null);
    }

    #[Test]
    public function store_response_includes_full_package_details(): void
    {
        [$talentUser, $talent, $package] = $this->createTalentWithPackage([
            'description' => 'Concert privé',
            'inclusions'  => ['Sono', 'Lumières'],
        ]);
        $client = $this->createClientUser();

        $this->actingAs($client, 'sanctum');

        $this->postJson('/api/v1/booking_requests', [
            'talent_profile_id'  => $talent->id,
            'service_package_id' => $package->id,
            'event_date'         => now()->addDays(20)->format('Y-m-d'),
            'event_location'     => 'Marcory, Abidjan',
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.devis.message', 'Cachet artiste intact — BookMi ajoute 15% de frais de service')
            ->assertJsonPath('data.service_package.description', 'Concert privé')
            ->assertJsonPath('data.service_package.inclusions', ['Sono', 'Lumières']);
    }
}
