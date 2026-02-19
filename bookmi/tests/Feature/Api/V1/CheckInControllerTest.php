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

class CheckInControllerTest extends TestCase
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

        return [$booking, $talent, $client];
    }

    private function seedToEnRoute(BookingRequest $booking, User $talent): void
    {
        TrackingEvent::factory()->create([
            'booking_request_id' => $booking->id,
            'updated_by'         => $talent->id,
            'status'             => TrackingStatus::Preparing,
        ]);
        TrackingEvent::factory()->create([
            'booking_request_id' => $booking->id,
            'updated_by'         => $talent->id,
            'status'             => TrackingStatus::EnRoute,
            'occurred_at'        => now()->addSecond(),
        ]);
    }

    // ── POST /booking_requests/{booking}/checkin ──────────────────────────────

    public function test_talent_can_check_in_after_en_route(): void
    {
        Event::fake([TrackingStatusChanged::class]);

        [$booking, $talent] = $this->makeBookingWithTalent();
        $this->seedToEnRoute($booking, $talent);

        $response = $this->actingAs($talent)->postJson(
            "/api/v1/booking_requests/{$booking->id}/checkin",
            ['latitude' => 5.349, 'longitude' => -4.008],
        );

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'arrived');
        $response->assertJsonPath('data.latitude', 5.349);
        $response->assertJsonPath('data.longitude', -4.008);

        $this->assertDatabaseHas('tracking_events', [
            'booking_request_id' => $booking->id,
            'status'             => 'arrived',
        ]);

        Event::assertDispatched(TrackingStatusChanged::class);
    }

    public function test_check_in_without_prior_en_route_returns_422(): void
    {
        Event::fake([TrackingStatusChanged::class]);

        [$booking, $talent] = $this->makeBookingWithTalent();

        // Only `preparing` status — no en_route
        TrackingEvent::factory()->create([
            'booking_request_id' => $booking->id,
            'updated_by'         => $talent->id,
            'status'             => TrackingStatus::Preparing,
        ]);

        $this->actingAs($talent)->postJson(
            "/api/v1/booking_requests/{$booking->id}/checkin",
            ['latitude' => 5.349, 'longitude' => -4.008],
        )->assertUnprocessable();

        Event::assertNotDispatched(TrackingStatusChanged::class);
    }

    public function test_check_in_requires_latitude(): void
    {
        [$booking, $talent] = $this->makeBookingWithTalent();
        $this->seedToEnRoute($booking, $talent);

        $response = $this->actingAs($talent)->postJson(
            "/api/v1/booking_requests/{$booking->id}/checkin",
            ['longitude' => -4.008],
        );

        $response->assertUnprocessable();
        $this->assertArrayHasKey('latitude', $response->json('error.details.errors'));
    }

    public function test_check_in_requires_longitude(): void
    {
        [$booking, $talent] = $this->makeBookingWithTalent();
        $this->seedToEnRoute($booking, $talent);

        $response = $this->actingAs($talent)->postJson(
            "/api/v1/booking_requests/{$booking->id}/checkin",
            ['latitude' => 5.349],
        );

        $response->assertUnprocessable();
        $this->assertArrayHasKey('longitude', $response->json('error.details.errors'));
    }

    public function test_client_cannot_check_in(): void
    {
        [$booking, $talent, $client] = $this->makeBookingWithTalent();

        $this->actingAs($client)->postJson(
            "/api/v1/booking_requests/{$booking->id}/checkin",
            ['latitude' => 5.349, 'longitude' => -4.008],
        )->assertForbidden();
    }

    public function test_checkin_on_pending_booking_returns_422(): void
    {
        [$booking, $talent] = $this->makeBookingWithTalent(BookingStatus::Pending);

        $this->actingAs($talent)->postJson(
            "/api/v1/booking_requests/{$booking->id}/checkin",
            ['latitude' => 5.349, 'longitude' => -4.008],
        )->assertUnprocessable();
    }

    public function test_unauthenticated_receives_401(): void
    {
        [$booking] = $this->makeBookingWithTalent();

        $this->postJson(
            "/api/v1/booking_requests/{$booking->id}/checkin",
            ['latitude' => 5.349, 'longitude' => -4.008],
        )->assertUnauthorized();
    }
}
