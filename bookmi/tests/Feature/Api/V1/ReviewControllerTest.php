<?php

namespace Tests\Feature\Api\V1;

use App\Enums\BookingStatus;
use App\Enums\ReviewType;
use App\Enums\UserRole;
use App\Models\BookingRequest;
use App\Models\Review;
use App\Models\TalentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Tests\TestCase;

class ReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([ThrottleRequests::class, ThrottleRequestsWithRedis::class]);
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    }

    private function makeCompletedBooking(): array
    {
        $client  = User::factory()->create();
        $client->assignRole(UserRole::CLIENT->value);

        $talent  = User::factory()->create();
        $talent->assignRole(UserRole::TALENT->value);
        $profile = TalentProfile::factory()->create(['user_id' => $talent->id]);

        $booking = BookingRequest::factory()->create([
            'client_id'         => $client->id,
            'talent_profile_id' => $profile->id,
            'status'            => BookingStatus::Completed,
        ]);

        return [$booking, $client, $talent, $profile];
    }

    // ── POST /booking_requests/{booking}/reviews ──────────────────────────────

    public function test_client_can_submit_review_for_talent(): void
    {
        [$booking, $client, $talent, $profile] = $this->makeCompletedBooking();

        $response = $this->actingAs($client)->postJson(
            "/api/v1/booking_requests/{$booking->id}/reviews",
            ['type' => 'client_to_talent', 'rating' => 5, 'comment' => 'Excellent !'],
        );

        $response->assertCreated();
        $response->assertJsonPath('data.type', 'client_to_talent');
        $response->assertJsonPath('data.rating', 5);

        $this->assertDatabaseHas('reviews', [
            'booking_request_id' => $booking->id,
            'type'               => 'client_to_talent',
            'rating'             => 5,
        ]);
    }

    public function test_talent_can_submit_review_for_client(): void
    {
        [$booking, $client, $talent] = $this->makeCompletedBooking();

        $response = $this->actingAs($talent)->postJson(
            "/api/v1/booking_requests/{$booking->id}/reviews",
            ['type' => 'talent_to_client', 'rating' => 4],
        );

        $response->assertCreated();
        $response->assertJsonPath('data.type', 'talent_to_client');
    }

    public function test_client_cannot_submit_talent_to_client_review(): void
    {
        [$booking, $client] = $this->makeCompletedBooking();

        $this->actingAs($client)->postJson(
            "/api/v1/booking_requests/{$booking->id}/reviews",
            ['type' => 'talent_to_client', 'rating' => 4],
        )->assertUnprocessable();
    }

    public function test_talent_cannot_submit_client_to_talent_review(): void
    {
        [$booking, $client, $talent] = $this->makeCompletedBooking();

        $this->actingAs($talent)->postJson(
            "/api/v1/booking_requests/{$booking->id}/reviews",
            ['type' => 'client_to_talent', 'rating' => 4],
        )->assertUnprocessable();
    }

    public function test_duplicate_review_is_rejected(): void
    {
        [$booking, $client, $talent, $profile] = $this->makeCompletedBooking();

        $this->actingAs($client)->postJson(
            "/api/v1/booking_requests/{$booking->id}/reviews",
            ['type' => 'client_to_talent', 'rating' => 5],
        )->assertCreated();

        $this->actingAs($client)->postJson(
            "/api/v1/booking_requests/{$booking->id}/reviews",
            ['type' => 'client_to_talent', 'rating' => 3],
        )->assertUnprocessable();
    }

    public function test_review_on_non_completed_booking_rejected(): void
    {
        $client  = User::factory()->create();
        $client->assignRole(UserRole::CLIENT->value);
        $profile = TalentProfile::factory()->create();

        $booking = BookingRequest::factory()->create([
            'client_id'         => $client->id,
            'talent_profile_id' => $profile->id,
            'status'            => BookingStatus::Paid,
        ]);

        $this->actingAs($client)->postJson(
            "/api/v1/booking_requests/{$booking->id}/reviews",
            ['type' => 'client_to_talent', 'rating' => 5],
        )->assertUnprocessable();
    }

    public function test_average_rating_is_updated_after_client_review(): void
    {
        [$booking, $client, $talent, $profile] = $this->makeCompletedBooking();

        $this->actingAs($client)->postJson(
            "/api/v1/booking_requests/{$booking->id}/reviews",
            ['type' => 'client_to_talent', 'rating' => 4],
        )->assertCreated();

        $profile->refresh();
        $this->assertEquals(4.0, (float) $profile->average_rating);
    }

    public function test_rating_must_be_between_1_and_5(): void
    {
        [$booking, $client] = $this->makeCompletedBooking();

        $this->actingAs($client)->postJson(
            "/api/v1/booking_requests/{$booking->id}/reviews",
            ['type' => 'client_to_talent', 'rating' => 6],
        )->assertUnprocessable();

        $this->actingAs($client)->postJson(
            "/api/v1/booking_requests/{$booking->id}/reviews",
            ['type' => 'client_to_talent', 'rating' => 0],
        )->assertUnprocessable();
    }

    public function test_unrelated_user_receives_403(): void
    {
        [$booking] = $this->makeCompletedBooking();
        $stranger = User::factory()->create();
        $stranger->assignRole(UserRole::CLIENT->value);

        $this->actingAs($stranger)->postJson(
            "/api/v1/booking_requests/{$booking->id}/reviews",
            ['type' => 'client_to_talent', 'rating' => 5],
        )->assertForbidden();
    }

    // ── GET /booking_requests/{booking}/reviews ───────────────────────────────

    public function test_participant_can_list_reviews(): void
    {
        [$booking, $client, $talent, $profile] = $this->makeCompletedBooking();

        Review::factory()->create([
            'booking_request_id' => $booking->id,
            'reviewer_id'        => $client->id,
            'reviewee_id'        => $talent->id,
            'type'               => ReviewType::ClientToTalent,
            'rating'             => 5,
        ]);

        $response = $this->actingAs($client)->getJson("/api/v1/booking_requests/{$booking->id}/reviews");

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_unauthenticated_receives_401(): void
    {
        [$booking] = $this->makeCompletedBooking();

        $this->postJson("/api/v1/booking_requests/{$booking->id}/reviews", ['type' => 'client_to_talent', 'rating' => 5])
            ->assertUnauthorized();
    }
}
