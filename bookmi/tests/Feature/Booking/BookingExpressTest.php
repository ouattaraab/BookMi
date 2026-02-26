<?php

namespace Tests\Feature\Booking;

use App\Enums\BookingStatus;
use App\Jobs\GenerateContractPdf;
use App\Models\BookingRequest;
use App\Models\ServicePackage;
use App\Models\TalentProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingExpressTest extends TestCase
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

    private function createTalentWithPackage(bool $expressEnabled = false): array
    {
        $user = User::factory()->create([
            'phone_verified_at' => now(),
            'is_active'         => true,
        ]);
        $user->assignRole('talent');

        $talentFactory = TalentProfile::factory()->verified();
        if ($expressEnabled) {
            $talentFactory = $talentFactory->withExpressBooking();
        }
        $talent  = $talentFactory->create(['user_id' => $user->id]);
        $package = ServicePackage::factory()->create([
            'talent_profile_id' => $talent->id,
            'cachet_amount'     => 10_000_000,
            'is_active'         => true,
        ]);

        return [$user, $talent, $package];
    }

    // ─── AC1: Réservation express acceptée automatiquement ─────────────────────

    #[Test]
    public function express_booking_is_automatically_accepted(): void
    {
        Bus::fake([GenerateContractPdf::class]);

        [, $talent, $package] = $this->createTalentWithPackage(expressEnabled: true);
        $client = $this->createClientUser();

        $this->actingAs($client, 'sanctum');

        $response = $this->postJson('/api/v1/booking_requests', [
            'talent_profile_id'  => $talent->id,
            'service_package_id' => $package->id,
            'event_date'         => now()->addDays(20)->format('Y-m-d'),
            'event_location'     => 'Plateau, Abidjan',
            'is_express'         => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', BookingStatus::Accepted->value)
            ->assertJsonPath('data.is_express', true);

        $this->assertDatabaseHas('booking_requests', [
            'client_id'  => $client->id,
            'is_express' => true,
            'status'     => BookingStatus::Accepted->value,
        ]);
    }

    #[Test]
    public function express_booking_dispatches_generate_contract_pdf_job(): void
    {
        Bus::fake([GenerateContractPdf::class]);

        [, $talent, $package] = $this->createTalentWithPackage(expressEnabled: true);
        $client = $this->createClientUser();

        $this->actingAs($client, 'sanctum');

        $this->postJson('/api/v1/booking_requests', [
            'talent_profile_id'  => $talent->id,
            'service_package_id' => $package->id,
            'event_date'         => now()->addDays(20)->format('Y-m-d'),
            'event_location'     => 'Plateau, Abidjan',
            'is_express'         => true,
        ])->assertStatus(201);

        Bus::assertDispatchedSync(GenerateContractPdf::class);
    }

    // ─── AC2: Talent sans express → 422 ────────────────────────────────────────

    #[Test]
    public function express_booking_fails_when_talent_has_not_enabled_it(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage(expressEnabled: false);
        $client = $this->createClientUser();

        $this->actingAs($client, 'sanctum');

        $this->postJson('/api/v1/booking_requests', [
            'talent_profile_id'  => $talent->id,
            'service_package_id' => $package->id,
            'event_date'         => now()->addDays(20)->format('Y-m-d'),
            'event_location'     => 'Plateau, Abidjan',
            'is_express'         => true,
        ])->assertStatus(422)
            ->assertJsonPath('error.code', 'BOOKING_EXPRESS_NOT_AVAILABLE');
    }

    // ─── AC3: Réservation normale sans is_express reste pending ────────────────

    #[Test]
    public function non_express_booking_stays_pending(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage(expressEnabled: true);
        $client = $this->createClientUser();

        $this->actingAs($client, 'sanctum');

        $response = $this->postJson('/api/v1/booking_requests', [
            'talent_profile_id'  => $talent->id,
            'service_package_id' => $package->id,
            'event_date'         => now()->addDays(20)->format('Y-m-d'),
            'event_location'     => 'Plateau, Abidjan',
            'is_express'         => false,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', BookingStatus::Pending->value)
            ->assertJsonPath('data.is_express', false);
    }

    // ─── AC4: is_express dans la resource ──────────────────────────────────────

    #[Test]
    public function is_express_field_is_exposed_in_booking_resource(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage(expressEnabled: true);
        $client = $this->createClientUser();

        $booking = BookingRequest::create([
            'client_id'          => $client->id,
            'talent_profile_id'  => $talent->id,
            'service_package_id' => $package->id,
            'event_date'         => now()->addDays(20)->format('Y-m-d'),
            'event_location'     => 'Plateau, Abidjan',
            'is_express'         => true,
            'status'             => BookingStatus::Accepted,
            'cachet_amount'      => 10_000_000,
            'commission_amount'  => 1_500_000,
            'total_amount'       => 11_500_000,
        ]);

        $this->actingAs($client, 'sanctum');

        $this->getJson("/api/v1/booking_requests/{$booking->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.is_express', true);
    }
}
