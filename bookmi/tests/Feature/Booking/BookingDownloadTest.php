<?php

namespace Tests\Feature\Booking;

use App\Enums\BookingStatus;
use App\Models\BookingRequest;
use App\Models\ServicePackage;
use App\Models\TalentProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('local');
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

        return [$talent, $package];
    }

    private function createPaidBooking(User $client, TalentProfile $talent, ServicePackage $package): BookingRequest
    {
        $cachet     = $package->cachet_amount;
        $commission = (int) round($cachet * 0.15);

        return BookingRequest::create([
            'client_id'          => $client->id,
            'talent_profile_id'  => $talent->id,
            'service_package_id' => $package->id,
            'event_date'         => now()->addDays(15)->format('Y-m-d'),
            'event_location'     => 'Cocody, Abidjan',
            'status'             => BookingStatus::Paid,
            'cachet_amount'      => $cachet,
            'commission_amount'  => $commission,
            'total_amount'       => $cachet + $commission,
        ]);
    }

    private function createPendingBooking(User $client, TalentProfile $talent, ServicePackage $package): BookingRequest
    {
        $cachet     = $package->cachet_amount;
        $commission = (int) round($cachet * 0.15);

        return BookingRequest::create([
            'client_id'          => $client->id,
            'talent_profile_id'  => $talent->id,
            'service_package_id' => $package->id,
            'event_date'         => now()->addDays(15)->format('Y-m-d'),
            'event_location'     => 'Cocody, Abidjan',
            'status'             => BookingStatus::Pending,
            'cachet_amount'      => $cachet,
            'commission_amount'  => $commission,
            'total_amount'       => $cachet + $commission,
        ]);
    }

    // ─── Receipt URL ──────────────────────────────────────────────────────────

    #[Test]
    public function client_can_get_receipt_url_for_paid_booking(): void
    {
        [$talent, $package] = $this->createTalentWithPackage();
        $client             = $this->createClientUser();
        $booking            = $this->createPaidBooking($client, $talent, $package);

        $this->actingAs($client, 'sanctum');

        $response = $this->getJson("/api/v1/booking_requests/{$booking->id}/receipt");

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['receipt_url']])
            ->assertJsonPath('data.receipt_url', fn ($url) => str_contains($url, '/api/v1/dl/'));
    }

    #[Test]
    public function receipt_url_not_available_for_pending_booking(): void
    {
        [$talent, $package] = $this->createTalentWithPackage();
        $client             = $this->createClientUser();
        $booking            = $this->createPendingBooking($client, $talent, $package);

        $this->actingAs($client, 'sanctum');

        $this->getJson("/api/v1/booking_requests/{$booking->id}/receipt")
            ->assertStatus(404)
            ->assertJsonPath('error.code', 'RECEIPT_NOT_AVAILABLE');
    }

    #[Test]
    public function other_user_cannot_get_receipt_url(): void
    {
        [$talent, $package] = $this->createTalentWithPackage();
        $client             = $this->createClientUser();
        $otherClient        = $this->createClientUser();
        $booking            = $this->createPaidBooking($client, $talent, $package);

        $this->actingAs($otherClient, 'sanctum');

        $this->getJson("/api/v1/booking_requests/{$booking->id}/receipt")
            ->assertStatus(403);
    }

    // ─── Contract URL ─────────────────────────────────────────────────────────

    #[Test]
    public function client_can_get_contract_url_when_contract_exists(): void
    {
        [$talent, $package] = $this->createTalentWithPackage();
        $client             = $this->createClientUser();
        $booking            = $this->createPaidBooking($client, $talent, $package);

        // Pre-create a fake contract file
        $path = "contracts/booking-{$booking->id}.pdf";
        Storage::disk('local')->put($path, '%PDF-1.4 fake contract content');
        $booking->update(['contract_path' => $path]);

        $this->actingAs($client, 'sanctum');

        $response = $this->getJson("/api/v1/booking_requests/{$booking->id}/contract-url");

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['contract_url']])
            ->assertJsonPath('data.contract_url', fn ($url) => str_contains($url, '/api/v1/dl/'));
    }

    #[Test]
    public function contract_url_returns_error_when_no_contract_file(): void
    {
        [$talent, $package] = $this->createTalentWithPackage();
        $client             = $this->createClientUser();
        $booking            = $this->createPaidBooking($client, $talent, $package);
        // contract_path is null — no contract generated

        $this->actingAs($client, 'sanctum');

        $this->getJson("/api/v1/booking_requests/{$booking->id}/contract-url")
            ->assertStatus(404)
            ->assertJsonPath('error.code', 'BOOKING_CONTRACT_NOT_READY');
    }

    #[Test]
    public function contract_url_not_available_for_pending_booking(): void
    {
        [$talent, $package] = $this->createTalentWithPackage();
        $client             = $this->createClientUser();
        $booking            = $this->createPendingBooking($client, $talent, $package);

        $this->actingAs($client, 'sanctum');

        $this->getJson("/api/v1/booking_requests/{$booking->id}/contract-url")
            ->assertStatus(404)
            ->assertJsonPath('error.code', 'CONTRACT_NOT_AVAILABLE');
    }

    // ─── Download endpoint /dl/{token} ────────────────────────────────────────

    #[Test]
    public function download_contract_via_valid_token(): void
    {
        [$talent, $package] = $this->createTalentWithPackage();
        $client             = $this->createClientUser();
        $booking            = $this->createPaidBooking($client, $talent, $package);

        $path = "contracts/booking-{$booking->id}.pdf";
        Storage::disk('local')->put($path, '%PDF-1.4 fake contract content');
        $booking->update(['contract_path' => $path]);

        $token = (string) Str::uuid();
        Cache::put("pdf_download:{$token}", [
            'type'       => 'contract',
            'booking_id' => $booking->id,
        ], now()->addMinutes(10));

        $response = $this->get("/api/v1/dl/{$token}");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');
    }

    #[Test]
    public function token_is_single_use_for_contract_download(): void
    {
        [$talent, $package] = $this->createTalentWithPackage();
        $client             = $this->createClientUser();
        $booking            = $this->createPaidBooking($client, $talent, $package);

        $path = "contracts/booking-{$booking->id}.pdf";
        Storage::disk('local')->put($path, '%PDF-1.4 fake contract content');
        $booking->update(['contract_path' => $path]);

        $token = (string) Str::uuid();
        Cache::put("pdf_download:{$token}", [
            'type'       => 'contract',
            'booking_id' => $booking->id,
        ], now()->addMinutes(10));

        // First request succeeds
        $this->get("/api/v1/dl/{$token}")->assertStatus(200);

        // Second request with same token → 410
        $this->get("/api/v1/dl/{$token}")->assertStatus(410);
    }

    #[Test]
    public function expired_or_invalid_token_returns_410(): void
    {
        $this->get('/api/v1/dl/' . Str::uuid())
            ->assertStatus(410);
    }
}
