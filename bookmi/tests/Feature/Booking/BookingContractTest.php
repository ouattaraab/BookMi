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
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingContractTest extends TestCase
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

    private function createTalentWithPackage(): array
    {
        $user = User::factory()->create([
            'phone_verified_at' => now(),
            'is_active'         => true,
        ]);
        $user->assignRole('talent');

        $talent  = TalentProfile::factory()->verified()->create(['user_id' => $user->id]);
        $package = ServicePackage::factory()->create([
            'talent_profile_id' => $talent->id,
            'cachet_amount'     => 10_000_000,
            'is_active'         => true,
        ]);

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

    // ─── AC1: Job dispatché à l'acceptation ───────────────────────────────────

    #[Test]
    public function accepting_a_booking_dispatches_generate_contract_pdf_job(): void
    {
        Queue::fake([GenerateContractPdf::class]);

        [$talentUser, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createBooking($client, $talent, $package);

        $this->actingAs($talentUser, 'sanctum');

        $this->postJson("/api/v1/booking_requests/{$booking->id}/accept")
            ->assertStatus(200);

        Queue::assertPushedOn('media', GenerateContractPdf::class, fn ($job) => $job->booking->id === $booking->id);
    }

    // ─── AC3 & AC4: Téléchargement du contrat ─────────────────────────────────

    #[Test]
    public function client_can_download_contract_when_ready(): void
    {
        Storage::fake('local');

        [, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createBooking($client, $talent, $package, [
            'status'        => BookingStatus::Accepted,
            'contract_path' => "contracts/booking-99.pdf",
        ]);

        // Simulate the job having already run and stored the file
        Storage::disk('local')->put("contracts/booking-99.pdf", '%PDF fake content');

        $this->actingAs($client, 'sanctum');

        $response = $this->get("/api/v1/booking_requests/{$booking->id}/contract");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');
    }

    #[Test]
    public function talent_can_download_contract_when_ready(): void
    {
        Storage::fake('local');

        [$talentUser, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createBooking($client, $talent, $package, [
            'status'        => BookingStatus::Accepted,
            'contract_path' => "contracts/booking-99.pdf",
        ]);

        Storage::disk('local')->put("contracts/booking-99.pdf", '%PDF fake content');

        $this->actingAs($talentUser, 'sanctum');

        $this->get("/api/v1/booking_requests/{$booking->id}/contract")
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');
    }

    #[Test]
    public function returns_404_when_contract_not_yet_generated(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createBooking($client, $talent, $package, [
            'status'        => BookingStatus::Accepted,
            'contract_path' => null,
        ]);

        $this->actingAs($client, 'sanctum');

        $this->getJson("/api/v1/booking_requests/{$booking->id}/contract")
            ->assertStatus(404)
            ->assertJsonPath('error.code', 'BOOKING_CONTRACT_NOT_READY');
    }

    // ─── AC5: Autorisation ────────────────────────────────────────────────────

    #[Test]
    public function third_party_cannot_download_contract(): void
    {
        Storage::fake('local');

        [, $talent, $package] = $this->createTalentWithPackage();
        $client     = $this->createClientUser();
        $thirdParty = $this->createClientUser();
        $booking    = $this->createBooking($client, $talent, $package, [
            'status'        => BookingStatus::Accepted,
            'contract_path' => "contracts/booking-99.pdf",
        ]);

        Storage::disk('local')->put("contracts/booking-99.pdf", '%PDF fake content');

        $this->actingAs($thirdParty, 'sanctum');

        $this->get("/api/v1/booking_requests/{$booking->id}/contract")
            ->assertStatus(403);
    }

    // ─── AC6: contract_available dans le resource ──────────────────────────────

    #[Test]
    public function contract_available_is_false_when_no_contract(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createBooking($client, $talent, $package);

        $this->actingAs($client, 'sanctum');

        $this->getJson("/api/v1/booking_requests/{$booking->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.contract_available', false);
    }

    #[Test]
    public function contract_available_is_true_when_contract_exists(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createBooking($client, $talent, $package, [
            'status'        => BookingStatus::Accepted,
            'contract_path' => 'contracts/booking-99.pdf',
        ]);

        $this->actingAs($client, 'sanctum');

        $this->getJson("/api/v1/booking_requests/{$booking->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.contract_available', true);
    }
}
