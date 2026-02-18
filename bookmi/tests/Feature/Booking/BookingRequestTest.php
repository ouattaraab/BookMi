<?php

namespace Tests\Feature\Booking;

use App\Enums\BookingStatus;
use App\Enums\CalendarSlotStatus;
use App\Events\BookingCreated;
use App\Models\BookingRequest;
use App\Models\CalendarSlot;
use App\Models\ServicePackage;
use App\Models\TalentProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingRequestTest extends TestCase
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
        $cachet     = $package->cachet_amount;
        $commission = (int) round($cachet * 0.15);

        return BookingRequest::create(array_merge([
            'client_id'          => $client->id,
            'talent_profile_id'  => $talent->id,
            'service_package_id' => $package->id,
            'event_date'         => now()->addDays(15)->format('Y-m-d'),
            'event_location'     => 'Cocody, Abidjan',
            'message'            => null,
            'status'             => BookingStatus::Pending,
            'cachet_amount'      => $cachet,
            'commission_amount'  => $commission,
            'total_amount'       => $cachet + $commission,
        ], $attrs));
    }

    // ─── AC1: Créer une réservation ───────────────────────────────────────────

    #[Test]
    public function client_can_create_booking_request(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();
        $client = $this->createClientUser();
        $this->actingAs($client, 'sanctum');

        $response = $this->postJson('/api/v1/booking_requests', [
            'talent_profile_id'  => $talent->id,
            'service_package_id' => $package->id,
            'event_date'         => now()->addDays(10)->format('Y-m-d'),
            'event_location'     => 'Cocody, Abidjan',
            'message'            => 'Anniversaire surprise',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id', 'status', 'client', 'talent_profile',
                    'service_package', 'event_date', 'event_location',
                    'message', 'devis',
                ],
            ])
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.client.id', $client->id);

        $this->assertDatabaseHas('booking_requests', [
            'client_id'         => $client->id,
            'talent_profile_id' => $talent->id,
            'status'            => 'pending',
        ]);
    }

    #[Test]
    public function booking_has_correct_commission_calculation(): void
    {
        // package cachet_amount = 10_000_000 → commission 15% = 1_500_000
        [, $talent, $package] = $this->createTalentWithPackage();
        $client = $this->createClientUser();
        $this->actingAs($client, 'sanctum');

        $this->postJson('/api/v1/booking_requests', [
            'talent_profile_id'  => $talent->id,
            'service_package_id' => $package->id,
            'event_date'         => now()->addDays(10)->format('Y-m-d'),
            'event_location'     => 'Plateau, Abidjan',
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.devis.cachet_amount', 10_000_000)
            ->assertJsonPath('data.devis.commission_amount', 1_500_000)
            ->assertJsonPath('data.devis.total_amount', 11_500_000);
    }

    #[Test]
    public function booking_creation_fires_booking_created_event(): void
    {
        Event::fake([BookingCreated::class]);

        [, $talent, $package] = $this->createTalentWithPackage();
        $client = $this->createClientUser();
        $this->actingAs($client, 'sanctum');

        $this->postJson('/api/v1/booking_requests', [
            'talent_profile_id'  => $talent->id,
            'service_package_id' => $package->id,
            'event_date'         => now()->addDays(10)->format('Y-m-d'),
            'event_location'     => 'Yopougon, Abidjan',
        ])->assertStatus(201);

        Event::assertDispatched(BookingCreated::class);
    }

    // ─── AC2: Vérification de disponibilité ───────────────────────────────────

    #[Test]
    public function booking_refused_when_date_is_blocked(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();
        $client = $this->createClientUser();
        $date   = now()->addDays(7)->format('Y-m-d');

        CalendarSlot::factory()->create([
            'talent_profile_id' => $talent->id,
            'date'              => $date,
            'status'            => CalendarSlotStatus::Blocked,
        ]);

        $this->actingAs($client, 'sanctum');

        $this->postJson('/api/v1/booking_requests', [
            'talent_profile_id'  => $talent->id,
            'service_package_id' => $package->id,
            'event_date'         => $date,
            'event_location'     => 'Abidjan',
        ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'BOOKING_DATE_UNAVAILABLE');
    }

    #[Test]
    public function booking_refused_when_date_is_rest(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();
        $client = $this->createClientUser();
        $date   = now()->addDays(7)->format('Y-m-d');

        CalendarSlot::factory()->rest()->create([
            'talent_profile_id' => $talent->id,
            'date'              => $date,
        ]);

        $this->actingAs($client, 'sanctum');

        $this->postJson('/api/v1/booking_requests', [
            'talent_profile_id'  => $talent->id,
            'service_package_id' => $package->id,
            'event_date'         => $date,
            'event_location'     => 'Abidjan',
        ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'BOOKING_DATE_UNAVAILABLE');
    }

    #[Test]
    public function booking_allowed_when_date_has_no_slot(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();
        $client = $this->createClientUser();
        $this->actingAs($client, 'sanctum');

        $this->postJson('/api/v1/booking_requests', [
            'talent_profile_id'  => $talent->id,
            'service_package_id' => $package->id,
            'event_date'         => now()->addDays(20)->format('Y-m-d'),
            'event_location'     => 'Abidjan',
        ])->assertStatus(201);
    }

    #[Test]
    public function booking_allowed_when_date_is_available(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();
        $client = $this->createClientUser();
        $date   = now()->addDays(7)->format('Y-m-d');

        CalendarSlot::factory()->available()->create([
            'talent_profile_id' => $talent->id,
            'date'              => $date,
        ]);

        $this->actingAs($client, 'sanctum');

        $this->postJson('/api/v1/booking_requests', [
            'talent_profile_id'  => $talent->id,
            'service_package_id' => $package->id,
            'event_date'         => $date,
            'event_location'     => 'Abidjan',
        ])->assertStatus(201);
    }

    // ─── AC4: Validation des champs ───────────────────────────────────────────

    #[Test]
    public function booking_refused_for_unverified_talent(): void
    {
        $talentUser = User::factory()->create(['phone_verified_at' => now(), 'is_active' => true]);
        $talentUser->assignRole('talent');
        $talent  = TalentProfile::factory()->create(['user_id' => $talentUser->id, 'is_verified' => false]);
        $package = ServicePackage::factory()->create(['talent_profile_id' => $talent->id]);

        $client = $this->createClientUser();
        $this->actingAs($client, 'sanctum');

        $this->postJson('/api/v1/booking_requests', [
            'talent_profile_id'  => $talent->id,
            'service_package_id' => $package->id,
            'event_date'         => now()->addDays(10)->format('Y-m-d'),
            'event_location'     => 'Abidjan',
        ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure(['error' => ['details' => ['errors' => ['talent_profile_id']]]]);
    }

    #[Test]
    public function booking_refused_for_package_of_wrong_talent(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();
        [, $otherTalent]      = $this->createTalentWithPackage();

        $client = $this->createClientUser();
        $this->actingAs($client, 'sanctum');

        // package belongs to talent, but talent_profile_id points to otherTalent
        $this->postJson('/api/v1/booking_requests', [
            'talent_profile_id'  => $otherTalent->id,
            'service_package_id' => $package->id,
            'event_date'         => now()->addDays(10)->format('Y-m-d'),
            'event_location'     => 'Abidjan',
        ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure(['error' => ['details' => ['errors' => ['service_package_id']]]]);
    }

    #[Test]
    public function booking_refused_for_past_date(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();
        $client = $this->createClientUser();
        $this->actingAs($client, 'sanctum');

        $this->postJson('/api/v1/booking_requests', [
            'talent_profile_id'  => $talent->id,
            'service_package_id' => $package->id,
            'event_date'         => now()->subDay()->format('Y-m-d'),
            'event_location'     => 'Abidjan',
        ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure(['error' => ['details' => ['errors' => ['event_date']]]]);
    }

    #[Test]
    public function unauthenticated_user_cannot_create_booking(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();

        $this->postJson('/api/v1/booking_requests', [
            'talent_profile_id'  => $talent->id,
            'service_package_id' => $package->id,
            'event_date'         => now()->addDays(10)->format('Y-m-d'),
            'event_location'     => 'Abidjan',
        ])->assertStatus(401);
    }

    #[Test]
    public function booking_list_with_invalid_status_returns_422(): void
    {
        $client = $this->createClientUser();
        $this->actingAs($client, 'sanctum');

        $this->getJson('/api/v1/booking_requests?status=invalide')
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'BOOKING_INVALID_STATUS');
    }

    // ─── AC5: Accès à sa propre réservation ───────────────────────────────────

    #[Test]
    public function client_can_see_own_booking(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createBooking($client, $talent, $package);

        $this->actingAs($client, 'sanctum');

        $this->getJson("/api/v1/booking_requests/{$booking->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $booking->id);
    }

    #[Test]
    public function talent_can_see_booking_for_their_profile(): void
    {
        [$talentUser, $talent, $package] = $this->createTalentWithPackage();
        $client  = $this->createClientUser();
        $booking = $this->createBooking($client, $talent, $package);

        $this->actingAs($talentUser, 'sanctum');

        $this->getJson("/api/v1/booking_requests/{$booking->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $booking->id);
    }

    #[Test]
    public function third_party_cannot_see_booking(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();
        $client     = $this->createClientUser();
        $thirdParty = $this->createClientUser();
        $booking    = $this->createBooking($client, $talent, $package);

        $this->actingAs($thirdParty, 'sanctum');

        $this->getJson("/api/v1/booking_requests/{$booking->id}")
            ->assertStatus(403);
    }

    // ─── AC6: Lister les réservations ─────────────────────────────────────────

    #[Test]
    public function client_sees_own_bookings_list(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();
        $client      = $this->createClientUser();
        $otherClient = $this->createClientUser();

        $this->createBooking($client, $talent, $package);
        $this->createBooking($client, $talent, $package, ['event_date' => now()->addDays(20)->format('Y-m-d')]);
        $this->createBooking($otherClient, $talent, $package, ['event_date' => now()->addDays(25)->format('Y-m-d')]);

        $this->actingAs($client, 'sanctum');

        $this->getJson('/api/v1/booking_requests')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data', 'meta']);
    }

    #[Test]
    public function talent_sees_received_bookings_list(): void
    {
        [$talentUser, $talent, $package] = $this->createTalentWithPackage();
        $client1 = $this->createClientUser();
        $client2 = $this->createClientUser();

        $this->createBooking($client1, $talent, $package);
        $this->createBooking($client2, $talent, $package, ['event_date' => now()->addDays(20)->format('Y-m-d')]);

        // Booking for a different talent — must NOT appear
        [, $otherTalent, $otherPackage] = $this->createTalentWithPackage();
        $this->createBooking($client1, $otherTalent, $otherPackage, ['event_date' => now()->addDays(30)->format('Y-m-d')]);

        $this->actingAs($talentUser, 'sanctum');

        $this->getJson('/api/v1/booking_requests')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function booking_list_can_be_filtered_by_status(): void
    {
        [, $talent, $package] = $this->createTalentWithPackage();
        $client = $this->createClientUser();

        $this->createBooking($client, $talent, $package, ['status' => BookingStatus::Pending]);
        $this->createBooking($client, $talent, $package, [
            'event_date' => now()->addDays(20)->format('Y-m-d'),
            'status'     => BookingStatus::Accepted,
        ]);

        $this->actingAs($client, 'sanctum');

        $this->getJson('/api/v1/booking_requests?status=pending')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/v1/booking_requests?status=accepted')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}
