<?php

namespace Tests\Feature\Experience;

use App\Enums\ExperienceBookingStatus;
use App\Enums\ExperienceStatus;
use App\Models\ExperienceBooking;
use App\Models\PrivateExperience;
use App\Models\TalentProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExperienceBookingTest extends TestCase
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

    private function createTalentWithExperience(array $experienceAttrs = []): array
    {
        $talentUser = User::factory()->create([
            'phone_verified_at' => now(),
            'is_active'         => true,
        ]);
        $talentUser->assignRole('talent');

        $talent = TalentProfile::factory()->verified()->create(['user_id' => $talentUser->id]);

        $experience = PrivateExperience::factory()->create(array_merge([
            'talent_profile_id' => $talent->id,
            'total_price'       => 500_000,
            'max_seats'         => 10,
            'booked_seats'      => 0,
            'commission_rate'   => 15,
            'status'            => ExperienceStatus::Published,
        ], $experienceAttrs));

        return [$talentUser, $talent, $experience];
    }

    // ─── Public list & detail ─────────────────────────────────────────────────

    #[Test]
    public function guest_can_list_published_experiences(): void
    {
        [,, $exp] = $this->createTalentWithExperience();

        $this->getJson('/api/v1/experiences')
            ->assertStatus(200)
            ->assertJsonPath('data.0.id', $exp->id)
            ->assertJsonStructure([
                'data' => [['id', 'title', 'event_date', 'status', 'price_per_seat', 'max_seats', 'booked_seats', 'seats_available', 'is_full', 'talent']],
                'meta' => ['current_page', 'last_page', 'total'],
            ]);
    }

    #[Test]
    public function draft_experiences_are_hidden_from_public_list(): void
    {
        [,, $draft] = $this->createTalentWithExperience(['status' => ExperienceStatus::Draft]);

        $this->getJson('/api/v1/experiences')
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');

        $this->assertDatabaseHas('private_experiences', ['id' => $draft->id]);
    }

    #[Test]
    public function guest_can_view_experience_detail(): void
    {
        [,, $exp] = $this->createTalentWithExperience(['venue_revealed' => false]);

        $response = $this->getJson("/api/v1/experiences/{$exp->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $exp->id)
            ->assertJsonPath('data.venue_address', null); // hidden for guests
    }

    // ─── Client booking ───────────────────────────────────────────────────────

    #[Test]
    public function client_can_book_a_seat(): void
    {
        [,, $exp] = $this->createTalentWithExperience([
            'total_price' => 500_000,
            'max_seats'   => 10,
        ]);
        $client = $this->createClientUser();
        $this->actingAs($client, 'sanctum');

        $response = $this->postJson("/api/v1/experiences/{$exp->id}/book", [
            'seats_count' => 2,
        ]);

        // price_per_seat = 500_000 / 10 = 50_000 ; total = 100_000 ; commission = 15_000
        $response->assertStatus(201)
            ->assertJsonPath('data.seats_count', 2)
            ->assertJsonPath('data.total_amount', 100_000)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('experience_bookings', [
            'private_experience_id' => $exp->id,
            'client_id'             => $client->id,
            'seats_count'           => 2,
            'total_amount'          => 100_000,
            'commission_amount'     => 15_000,
            'status'                => 'pending',
        ]);
    }

    #[Test]
    public function booked_seats_counter_increments_after_booking(): void
    {
        [,, $exp] = $this->createTalentWithExperience(['booked_seats' => 3]);
        $client = $this->createClientUser();
        $this->actingAs($client, 'sanctum');

        $this->postJson("/api/v1/experiences/{$exp->id}/book", ['seats_count' => 2])
            ->assertStatus(201);

        $this->assertDatabaseHas('private_experiences', [
            'id'           => $exp->id,
            'booked_seats' => 5,
        ]);
    }

    #[Test]
    public function experience_becomes_full_when_last_seat_is_taken(): void
    {
        [,, $exp] = $this->createTalentWithExperience([
            'max_seats'   => 5,
            'booked_seats' => 4,
        ]);
        $client = $this->createClientUser();
        $this->actingAs($client, 'sanctum');

        $this->postJson("/api/v1/experiences/{$exp->id}/book", ['seats_count' => 1])
            ->assertStatus(201);

        $this->assertDatabaseHas('private_experiences', [
            'id'     => $exp->id,
            'status' => ExperienceStatus::Full->value,
        ]);
    }

    #[Test]
    public function client_cannot_book_more_seats_than_available(): void
    {
        [,, $exp] = $this->createTalentWithExperience([
            'max_seats'    => 5,
            'booked_seats' => 4,
        ]);
        $client = $this->createClientUser();
        $this->actingAs($client, 'sanctum');

        $this->postJson("/api/v1/experiences/{$exp->id}/book", ['seats_count' => 2])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Seulement 1 place(s) disponible(s).']);
    }

    #[Test]
    public function client_cannot_book_the_same_experience_twice(): void
    {
        [,, $exp] = $this->createTalentWithExperience();
        $client = $this->createClientUser();

        ExperienceBooking::create([
            'private_experience_id' => $exp->id,
            'client_id'             => $client->id,
            'seats_count'           => 1,
            'price_per_seat'        => 50_000,
            'total_amount'          => 50_000,
            'commission_amount'     => 7_500,
            'status'                => ExperienceBookingStatus::Pending,
        ]);

        $this->actingAs($client, 'sanctum');

        $this->postJson("/api/v1/experiences/{$exp->id}/book", ['seats_count' => 1])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Vous êtes déjà inscrit à cet événement.']);
    }

    #[Test]
    public function client_cannot_book_a_full_experience(): void
    {
        [,, $exp] = $this->createTalentWithExperience([
            'status'       => ExperienceStatus::Full,
            'max_seats'    => 5,
            'booked_seats' => 5,
        ]);
        $client = $this->createClientUser();
        $this->actingAs($client, 'sanctum');

        // Full experience is not Published → lockForUpdate query won't find it → 404
        $this->postJson("/api/v1/experiences/{$exp->id}/book", ['seats_count' => 1])
            ->assertStatus(404);
    }

    #[Test]
    public function unauthenticated_user_cannot_book(): void
    {
        [,, $exp] = $this->createTalentWithExperience();

        $this->postJson("/api/v1/experiences/{$exp->id}/book", ['seats_count' => 1])
            ->assertStatus(401);
    }

    // ─── Client cancellation ──────────────────────────────────────────────────

    #[Test]
    public function client_can_cancel_pending_booking(): void
    {
        [,, $exp] = $this->createTalentWithExperience(['booked_seats' => 2]);
        $client = $this->createClientUser();

        ExperienceBooking::create([
            'private_experience_id' => $exp->id,
            'client_id'             => $client->id,
            'seats_count'           => 2,
            'price_per_seat'        => 50_000,
            'total_amount'          => 100_000,
            'commission_amount'     => 15_000,
            'status'                => ExperienceBookingStatus::Pending,
        ]);

        $this->actingAs($client, 'sanctum');

        $this->deleteJson("/api/v1/experiences/{$exp->id}/booking")
            ->assertStatus(200)
            ->assertJsonFragment(['message' => 'Inscription annulée.']);

        $this->assertDatabaseHas('experience_bookings', [
            'private_experience_id' => $exp->id,
            'client_id'             => $client->id,
            'status'                => 'cancelled',
        ]);
    }

    #[Test]
    public function cancellation_restores_booked_seats_count(): void
    {
        [,, $exp] = $this->createTalentWithExperience(['booked_seats' => 3]);
        $client = $this->createClientUser();

        ExperienceBooking::create([
            'private_experience_id' => $exp->id,
            'client_id'             => $client->id,
            'seats_count'           => 3,
            'price_per_seat'        => 50_000,
            'total_amount'          => 150_000,
            'commission_amount'     => 22_500,
            'status'                => ExperienceBookingStatus::Pending,
        ]);

        $this->actingAs($client, 'sanctum');

        $this->deleteJson("/api/v1/experiences/{$exp->id}/booking")
            ->assertStatus(200);

        $this->assertDatabaseHas('private_experiences', [
            'id'           => $exp->id,
            'booked_seats' => 0,
        ]);
    }

    #[Test]
    public function cancellation_reverts_full_experience_to_published(): void
    {
        [,, $exp] = $this->createTalentWithExperience([
            'max_seats'    => 5,
            'booked_seats' => 5,
            'status'       => ExperienceStatus::Full,
        ]);
        $client = $this->createClientUser();

        ExperienceBooking::create([
            'private_experience_id' => $exp->id,
            'client_id'             => $client->id,
            'seats_count'           => 5,
            'price_per_seat'        => 50_000,
            'total_amount'          => 250_000,
            'commission_amount'     => 37_500,
            'status'                => ExperienceBookingStatus::Pending,
        ]);

        $this->actingAs($client, 'sanctum');

        $this->deleteJson("/api/v1/experiences/{$exp->id}/booking")
            ->assertStatus(200);

        $this->assertDatabaseHas('private_experiences', [
            'id'     => $exp->id,
            'status' => ExperienceStatus::Published->value,
        ]);
    }

    // ─── Talent creates experience via API ────────────────────────────────────

    #[Test]
    public function talent_can_create_experience_via_api(): void
    {
        [$talentUser] = $this->createTalentWithExperience();
        $this->actingAs($talentUser, 'sanctum');

        $response = $this->postJson('/api/v1/talent/experiences', [
            'title'         => 'Evening privé exclusif',
            'description'   => 'Une nuit inoubliable avec vos artistes préférés.',
            'event_date'    => now()->addMonth()->format('Y-m-d H:i:s'),
            'venue_address' => '10 Rue de la Paix, Abidjan',
            'total_price'   => 1_000_000,
            'max_seats'     => 20,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.max_seats', 20);

        $this->assertDatabaseHas('private_experiences', [
            'title'       => 'Evening privé exclusif',
            'total_price' => 1_000_000,
            'max_seats'   => 20,
            'status'      => 'draft',
        ]);
    }

    #[Test]
    public function talent_can_list_own_experiences(): void
    {
        [$talentUser,, $exp] = $this->createTalentWithExperience();
        $this->actingAs($talentUser, 'sanctum');

        $response = $this->getJson('/api/v1/talent/experiences');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $exp->id)
            ->assertJsonStructure([
                'data' => [['id', 'title', 'total_collected', 'talent_net', 'bookings_count']],
            ]);
    }

    #[Test]
    public function venue_is_revealed_to_booked_client(): void
    {
        [,, $exp] = $this->createTalentWithExperience([
            'venue_address'  => '10 Rue Secrète, Abidjan',
            'venue_revealed' => false,
        ]);
        $client = $this->createClientUser();

        ExperienceBooking::create([
            'private_experience_id' => $exp->id,
            'client_id'             => $client->id,
            'seats_count'           => 1,
            'price_per_seat'        => 50_000,
            'total_amount'          => 50_000,
            'commission_amount'     => 7_500,
            'status'                => ExperienceBookingStatus::Pending,
        ]);

        $this->actingAs($client, 'sanctum');

        $response = $this->getJson("/api/v1/experiences/{$exp->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.venue_address', '10 Rue Secrète, Abidjan');
    }

    #[Test]
    public function venue_is_hidden_from_non_booked_client(): void
    {
        [,, $exp] = $this->createTalentWithExperience([
            'venue_address'  => '10 Rue Secrète, Abidjan',
            'venue_revealed' => false,
        ]);
        $client = $this->createClientUser();
        $this->actingAs($client, 'sanctum');

        $response = $this->getJson("/api/v1/experiences/{$exp->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.venue_address', null);
    }
}
