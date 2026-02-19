<?php

namespace Tests\Feature\Api\V1;

use App\Enums\BookingStatus;
use App\Enums\TrackingStatus;
use App\Enums\UserRole;
use App\Events\TrackingStatusChanged;
use App\Models\BookingRequest;
use App\Models\TalentProfile;
use App\Models\TrackingEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TrackingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([ThrottleRequests::class, ThrottleRequestsWithRedis::class]);
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    }

    private function makeBookingWithTalent(BookingStatus $status = BookingStatus::Confirmed): array
    {
        $client  = User::factory()->create();
        $client->assignRole(UserRole::CLIENT->value);

        $talent  = User::factory()->create();
        $talent->assignRole(UserRole::TALENT->value);
        $profile = TalentProfile::factory()->create(['user_id' => $talent->id]);

        $booking = BookingRequest::factory()->create([
            'client_id'         => $client->id,
            'talent_profile_id' => $profile->id,
            'status'            => $status,
        ]);

        return [$booking, $talent, $client, $profile];
    }

    // ── POST /booking_requests/{booking}/tracking ─────────────────────────────

    public function test_talent_can_post_first_tracking_update_preparing(): void
    {
        Event::fake([TrackingStatusChanged::class]);

        [$booking, $talent] = $this->makeBookingWithTalent();

        $response = $this->actingAs($talent)->postJson(
            "/api/v1/booking_requests/{$booking->id}/tracking",
            ['status' => 'preparing', 'latitude' => 5.349, 'longitude' => -4.008],
        );

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'preparing');
        $response->assertJsonPath('data.status_label', 'En préparation');

        $this->assertDatabaseHas('tracking_events', [
            'booking_request_id' => $booking->id,
            'status'             => 'preparing',
        ]);

        Event::assertDispatched(TrackingStatusChanged::class);
    }

    public function test_talent_can_advance_status_in_sequence(): void
    {
        Event::fake([TrackingStatusChanged::class]);

        [$booking, $talent] = $this->makeBookingWithTalent();

        $statuses = ['preparing', 'en_route', 'arrived', 'performing', 'completed'];

        foreach ($statuses as $status) {
            $this->actingAs($talent)->postJson(
                "/api/v1/booking_requests/{$booking->id}/tracking",
                ['status' => $status],
            )->assertCreated();
        }

        $this->assertDatabaseCount('tracking_events', 5);
        Event::assertDispatchedTimes(TrackingStatusChanged::class, 5);
    }

    public function test_first_status_must_be_preparing(): void
    {
        Event::fake([TrackingStatusChanged::class]);

        [$booking, $talent] = $this->makeBookingWithTalent();

        $this->actingAs($talent)->postJson(
            "/api/v1/booking_requests/{$booking->id}/tracking",
            ['status' => 'en_route'],
        )->assertUnprocessable();

        Event::assertNotDispatched(TrackingStatusChanged::class);
    }

    public function test_backward_transition_is_rejected(): void
    {
        Event::fake([TrackingStatusChanged::class]);

        [$booking, $talent] = $this->makeBookingWithTalent();

        TrackingEvent::factory()->create([
            'booking_request_id' => $booking->id,
            'updated_by'         => $talent->id,
            'status'             => TrackingStatus::Arrived,
        ]);

        $this->actingAs($talent)->postJson(
            "/api/v1/booking_requests/{$booking->id}/tracking",
            ['status' => 'en_route'],
        )->assertUnprocessable();
    }

    public function test_duplicate_same_status_is_rejected(): void
    {
        [$booking, $talent] = $this->makeBookingWithTalent();

        TrackingEvent::factory()->create([
            'booking_request_id' => $booking->id,
            'updated_by'         => $talent->id,
            'status'             => TrackingStatus::Preparing,
        ]);

        $this->actingAs($talent)->postJson(
            "/api/v1/booking_requests/{$booking->id}/tracking",
            ['status' => 'preparing'],
        )->assertUnprocessable();
    }

    public function test_client_cannot_post_tracking_update(): void
    {
        [$booking, $talent, $client] = $this->makeBookingWithTalent();

        $this->actingAs($client)->postJson(
            "/api/v1/booking_requests/{$booking->id}/tracking",
            ['status' => 'preparing'],
        )->assertForbidden();
    }

    public function test_pending_booking_cannot_be_tracked(): void
    {
        [$booking, $talent] = $this->makeBookingWithTalent(BookingStatus::Pending);

        $this->actingAs($talent)->postJson(
            "/api/v1/booking_requests/{$booking->id}/tracking",
            ['status' => 'preparing'],
        )->assertUnprocessable();
    }

    public function test_paid_booking_can_be_tracked(): void
    {
        Event::fake([TrackingStatusChanged::class]);

        [$booking, $talent] = $this->makeBookingWithTalent(BookingStatus::Paid);

        $this->actingAs($talent)->postJson(
            "/api/v1/booking_requests/{$booking->id}/tracking",
            ['status' => 'preparing'],
        )->assertCreated();
    }

    // ── GET /booking_requests/{booking}/tracking ──────────────────────────────

    public function test_talent_can_list_tracking_events(): void
    {
        [$booking, $talent] = $this->makeBookingWithTalent();

        TrackingEvent::factory()->count(3)->sequence(
            ['status' => TrackingStatus::Preparing],
            ['status' => TrackingStatus::EnRoute],
            ['status' => TrackingStatus::Arrived],
        )->create(['booking_request_id' => $booking->id, 'updated_by' => $talent->id]);

        $response = $this->actingAs($talent)->getJson("/api/v1/booking_requests/{$booking->id}/tracking");

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
        $this->assertEquals('preparing', $response->json('data.0.status'));
    }

    public function test_client_can_list_tracking_events(): void
    {
        [$booking, $talent, $client] = $this->makeBookingWithTalent();

        TrackingEvent::factory()->create([
            'booking_request_id' => $booking->id,
            'updated_by'         => $talent->id,
            'status'             => TrackingStatus::Preparing,
        ]);

        $this->actingAs($client)->getJson("/api/v1/booking_requests/{$booking->id}/tracking")
            ->assertOk();
    }

    public function test_unrelated_user_cannot_list_tracking_events(): void
    {
        [$booking] = $this->makeBookingWithTalent();

        $stranger = User::factory()->create();
        $stranger->assignRole(UserRole::CLIENT->value);

        $this->actingAs($stranger)->getJson("/api/v1/booking_requests/{$booking->id}/tracking")
            ->assertForbidden();
    }

    public function test_unauthenticated_user_receives_401(): void
    {
        [$booking] = $this->makeBookingWithTalent();

        $this->postJson("/api/v1/booking_requests/{$booking->id}/tracking", ['status' => 'preparing'])
            ->assertUnauthorized();
    }

    public function test_invalid_latitude_is_rejected(): void
    {
        [$booking, $talent] = $this->makeBookingWithTalent();

        $this->actingAs($talent)->postJson(
            "/api/v1/booking_requests/{$booking->id}/tracking",
            ['status' => 'preparing', 'latitude' => 95.0, 'longitude' => 0.0],
        )->assertUnprocessable();
    }

    public function test_invalid_longitude_is_rejected(): void
    {
        [$booking, $talent] = $this->makeBookingWithTalent();

        $this->actingAs($talent)->postJson(
            "/api/v1/booking_requests/{$booking->id}/tracking",
            ['status' => 'preparing', 'latitude' => 5.0, 'longitude' => 200.0],
        )->assertUnprocessable();
    }

    public function test_cannot_post_after_completed_status(): void
    {
        [$booking, $talent] = $this->makeBookingWithTalent();

        TrackingEvent::factory()->create([
            'booking_request_id' => $booking->id,
            'updated_by'         => $talent->id,
            'status'             => TrackingStatus::Completed,
        ]);

        $this->actingAs($talent)->postJson(
            "/api/v1/booking_requests/{$booking->id}/tracking",
            ['status' => 'performing'],
        )->assertUnprocessable();
    }
}
