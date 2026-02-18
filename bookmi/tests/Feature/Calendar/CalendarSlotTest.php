<?php

namespace Tests\Feature\Calendar;

use App\Enums\CalendarSlotStatus;
use App\Models\CalendarSlot;
use App\Models\TalentProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CalendarSlotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function createTalentUser(): User
    {
        $user = User::factory()->create([
            'phone_verified_at' => now(),
            'is_active'         => true,
        ]);
        $user->assignRole('talent');

        return $user;
    }

    private function createTalentWithProfile(): array
    {
        $user    = $this->createTalentUser();
        $talent  = TalentProfile::factory()->verified()->create(['user_id' => $user->id]);

        return [$user, $talent];
    }

    // ─── AC1: Créer un créneau ─────────────────────────────────────────────────

    #[Test]
    public function talent_can_create_a_calendar_slot(): void
    {
        [$user, $talent] = $this->createTalentWithProfile();
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/v1/calendar_slots', [
            'date'   => now()->addDays(5)->format('Y-m-d'),
            'status' => 'blocked',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'talent_profile_id', 'date', 'status']])
            ->assertJsonPath('data.status', 'blocked');

        $this->assertDatabaseHas('calendar_slots', [
            'talent_profile_id' => $talent->id,
            'status'            => 'blocked',
        ]);
    }

    #[Test]
    public function talent_can_create_available_and_rest_slots(): void
    {
        [$user, $talent] = $this->createTalentWithProfile();
        $this->actingAs($user, 'sanctum');

        foreach (['available', 'rest'] as $i => $status) {
            $response = $this->postJson('/api/v1/calendar_slots', [
                'date'   => now()->addDays(10 + $i)->format('Y-m-d'),
                'status' => $status,
            ]);
            $response->assertStatus(201)
                ->assertJsonPath('data.status', $status);
        }
    }

    // ─── AC5: Conflit de dates ─────────────────────────────────────────────────

    #[Test]
    public function creating_duplicate_slot_for_same_date_returns_409(): void
    {
        [$user, $talent] = $this->createTalentWithProfile();
        $this->actingAs($user, 'sanctum');

        $date = now()->addDays(5)->format('Y-m-d');

        $this->postJson('/api/v1/calendar_slots', ['date' => $date, 'status' => 'blocked'])
            ->assertStatus(201);

        $this->postJson('/api/v1/calendar_slots', ['date' => $date, 'status' => 'available'])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'CALENDAR_SLOT_CONFLICT');
    }

    // ─── AC6: Validation ──────────────────────────────────────────────────────

    #[Test]
    public function creating_slot_with_past_date_returns_422(): void
    {
        [$user] = $this->createTalentWithProfile();
        $this->actingAs($user, 'sanctum');

        $this->postJson('/api/v1/calendar_slots', [
            'date'   => now()->subDay()->format('Y-m-d'),
            'status' => 'blocked',
        ])->assertStatus(422);
    }

    #[Test]
    public function creating_slot_with_invalid_status_returns_422(): void
    {
        [$user] = $this->createTalentWithProfile();
        $this->actingAs($user, 'sanctum');

        $this->postJson('/api/v1/calendar_slots', [
            'date'   => now()->addDay()->format('Y-m-d'),
            'status' => 'invalid_status',
        ])->assertStatus(422);
    }

    #[Test]
    public function creating_slot_with_confirmed_status_returns_422(): void
    {
        [$user] = $this->createTalentWithProfile();
        $this->actingAs($user, 'sanctum');

        $this->postJson('/api/v1/calendar_slots', [
            'date'   => now()->addDay()->format('Y-m-d'),
            'status' => 'confirmed', // virtual status — must be rejected
        ])->assertStatus(422);
    }

    #[Test]
    public function talent_user_without_profile_gets_clear_error(): void
    {
        $user = $this->createTalentUser(); // no TalentProfile created
        $this->actingAs($user, 'sanctum');

        $this->postJson('/api/v1/calendar_slots', [
            'date'   => now()->addDay()->format('Y-m-d'),
            'status' => 'blocked',
        ])->assertStatus(404)
          ->assertJsonPath('error.code', 'TALENT_PROFILE_NOT_FOUND');
    }

    #[Test]
    public function unauthenticated_user_cannot_create_slot(): void
    {
        $this->postJson('/api/v1/calendar_slots', [
            'date'   => now()->addDay()->format('Y-m-d'),
            'status' => 'blocked',
        ])->assertStatus(401);
    }

    // ─── AC2: Lister les disponibilités du mois ───────────────────────────────

    #[Test]
    public function anyone_can_view_talent_monthly_calendar(): void
    {
        [, $talent] = $this->createTalentWithProfile();

        CalendarSlot::factory()->create([
            'talent_profile_id' => $talent->id,
            'date'              => '2026-03-10',
            'status'            => CalendarSlotStatus::Blocked,
        ]);
        CalendarSlot::factory()->create([
            'talent_profile_id' => $talent->id,
            'date'              => '2026-03-15',
            'status'            => CalendarSlotStatus::Rest,
        ]);

        $response = $this->getJson("/api/v1/talents/{$talent->id}/calendar?month=2026-03");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['date' => '2026-03-10', 'status' => 'blocked'])
            ->assertJsonFragment(['date' => '2026-03-15', 'status' => 'rest']);
    }

    #[Test]
    public function calendar_returns_empty_array_when_no_slots(): void
    {
        [, $talent] = $this->createTalentWithProfile();

        $response = $this->getJson("/api/v1/talents/{$talent->id}/calendar?month=2026-06");

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function calendar_with_invalid_month_format_returns_422(): void
    {
        [, $talent] = $this->createTalentWithProfile();

        $this->getJson("/api/v1/talents/{$talent->id}/calendar?month=2026/03")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'CALENDAR_INVALID_MONTH');
    }

    #[Test]
    public function calendar_only_returns_slots_for_requested_month(): void
    {
        [, $talent] = $this->createTalentWithProfile();

        CalendarSlot::factory()->create([
            'talent_profile_id' => $talent->id,
            'date'              => '2026-03-05',
            'status'            => CalendarSlotStatus::Blocked,
        ]);
        CalendarSlot::factory()->create([
            'talent_profile_id' => $talent->id,
            'date'              => '2026-04-05', // different month
            'status'            => CalendarSlotStatus::Blocked,
        ]);

        $response = $this->getJson("/api/v1/talents/{$talent->id}/calendar?month=2026-03");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['date' => '2026-03-05']);
    }

    // ─── AC3: Modifier un créneau ─────────────────────────────────────────────

    #[Test]
    public function talent_can_update_own_slot(): void
    {
        [$user, $talent] = $this->createTalentWithProfile();
        $this->actingAs($user, 'sanctum');

        $slot = CalendarSlot::factory()->blocked()->create([
            'talent_profile_id' => $talent->id,
            'date'              => now()->addDays(7)->format('Y-m-d'),
        ]);

        $response = $this->putJson("/api/v1/calendar_slots/{$slot->id}", [
            'status' => 'available',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'available');

        $this->assertDatabaseHas('calendar_slots', [
            'id'     => $slot->id,
            'status' => 'available',
        ]);
    }

    #[Test]
    public function talent_cannot_update_another_talents_slot(): void
    {
        [$user1] = $this->createTalentWithProfile();
        [, $talent2] = $this->createTalentWithProfile();

        $slot = CalendarSlot::factory()->blocked()->create([
            'talent_profile_id' => $talent2->id,
            'date'              => now()->addDays(5)->format('Y-m-d'),
        ]);

        $this->actingAs($user1, 'sanctum');

        $this->putJson("/api/v1/calendar_slots/{$slot->id}", ['status' => 'available'])
            ->assertStatus(403);
    }

    // ─── AC4: Supprimer un créneau ────────────────────────────────────────────

    #[Test]
    public function talent_can_delete_own_slot(): void
    {
        [$user, $talent] = $this->createTalentWithProfile();
        $this->actingAs($user, 'sanctum');

        $slot = CalendarSlot::factory()->blocked()->create([
            'talent_profile_id' => $talent->id,
            'date'              => now()->addDays(10)->format('Y-m-d'),
        ]);

        $this->deleteJson("/api/v1/calendar_slots/{$slot->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('calendar_slots', ['id' => $slot->id]);
    }

    #[Test]
    public function talent_cannot_delete_another_talents_slot(): void
    {
        [$user1] = $this->createTalentWithProfile();
        [, $talent2] = $this->createTalentWithProfile();

        $slot = CalendarSlot::factory()->blocked()->create([
            'talent_profile_id' => $talent2->id,
            'date'              => now()->addDays(5)->format('Y-m-d'),
        ]);

        $this->actingAs($user1, 'sanctum');

        $this->deleteJson("/api/v1/calendar_slots/{$slot->id}")
            ->assertStatus(403);
    }

    #[Test]
    public function deleting_non_existent_slot_returns_404(): void
    {
        [$user] = $this->createTalentWithProfile();
        $this->actingAs($user, 'sanctum');

        $this->deleteJson('/api/v1/calendar_slots/99999')
            ->assertStatus(404);
    }
}
