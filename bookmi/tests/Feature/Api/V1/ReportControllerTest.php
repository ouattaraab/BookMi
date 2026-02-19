<?php

namespace Tests\Feature\Api\V1;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\BookingRequest;
use App\Models\TalentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([ThrottleRequests::class, ThrottleRequestsWithRedis::class]);
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    }

    private function makeBookingWithParticipants(BookingStatus $status = BookingStatus::Confirmed): array
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

        return [$booking, $client, $talent];
    }

    // ── POST /booking_requests/{booking}/reports ──────────────────────────────

    public function test_client_can_file_report_and_booking_becomes_disputed(): void
    {
        [$booking, $client] = $this->makeBookingWithParticipants(BookingStatus::Confirmed);

        $response = $this->actingAs($client)->postJson(
            "/api/v1/booking_requests/{$booking->id}/reports",
            ['reason' => 'no_show', 'description' => 'Le talent ne s\'est pas présenté.'],
        );

        $response->assertCreated();
        $response->assertJsonPath('data.reason', 'no_show');

        $this->assertDatabaseHas('reports', ['booking_request_id' => $booking->id, 'reason' => 'no_show']);
        $booking->refresh();
        $this->assertEquals(BookingStatus::Disputed, $booking->status);
    }

    public function test_talent_can_file_report(): void
    {
        [$booking, $client, $talent] = $this->makeBookingWithParticipants(BookingStatus::Paid);

        $this->actingAs($talent)->postJson(
            "/api/v1/booking_requests/{$booking->id}/reports",
            ['reason' => 'payment_issue'],
        )->assertCreated();

        $booking->refresh();
        $this->assertEquals(BookingStatus::Disputed, $booking->status);
    }

    public function test_report_on_completed_booking_does_not_change_status(): void
    {
        [$booking, $client] = $this->makeBookingWithParticipants(BookingStatus::Completed);

        $this->actingAs($client)->postJson(
            "/api/v1/booking_requests/{$booking->id}/reports",
            ['reason' => 'quality_issue'],
        )->assertCreated();

        $booking->refresh();
        $this->assertEquals(BookingStatus::Completed, $booking->status);
    }

    public function test_report_on_pending_booking_returns_422(): void
    {
        [$booking, $client] = $this->makeBookingWithParticipants(BookingStatus::Pending);

        $this->actingAs($client)->postJson(
            "/api/v1/booking_requests/{$booking->id}/reports",
            ['reason' => 'other'],
        )->assertUnprocessable();
    }

    public function test_unrelated_user_cannot_file_report(): void
    {
        [$booking] = $this->makeBookingWithParticipants();
        $stranger = User::factory()->create();
        $stranger->assignRole(UserRole::CLIENT->value);

        $this->actingAs($stranger)->postJson(
            "/api/v1/booking_requests/{$booking->id}/reports",
            ['reason' => 'other'],
        )->assertForbidden();
    }

    public function test_invalid_reason_returns_422(): void
    {
        [$booking, $client] = $this->makeBookingWithParticipants();

        $this->actingAs($client)->postJson(
            "/api/v1/booking_requests/{$booking->id}/reports",
            ['reason' => 'invalid_reason'],
        )->assertUnprocessable();
    }

    public function test_unauthenticated_receives_401(): void
    {
        [$booking] = $this->makeBookingWithParticipants();

        $this->postJson("/api/v1/booking_requests/{$booking->id}/reports", ['reason' => 'other'])
            ->assertUnauthorized();
    }
}
