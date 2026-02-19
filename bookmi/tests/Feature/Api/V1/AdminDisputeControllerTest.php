<?php

namespace Tests\Feature\Api\V1;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Events\AdminAccessedMessages;
use App\Models\BookingRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\TalentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AdminDisputeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([ThrottleRequests::class, ThrottleRequestsWithRedis::class]);
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    }

    private function makeAdmin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function makeClient(): User
    {
        $user = User::factory()->create();
        $user->assignRole(UserRole::CLIENT->value);
        return $user;
    }

    private function makeDisputedBookingWithConversation(): array
    {
        $client  = $this->makeClient();
        $talent  = User::factory()->create();
        $profile = TalentProfile::factory()->create(['user_id' => $talent->id]);

        $booking = BookingRequest::factory()->create([
            'client_id'         => $client->id,
            'talent_profile_id' => $profile->id,
            'status'            => BookingStatus::Disputed,
        ]);

        $conversation = Conversation::factory()->create([
            'client_id'          => $client->id,
            'talent_profile_id'  => $profile->id,
            'booking_request_id' => $booking->id,
        ]);

        Message::factory()->count(3)->create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $client->id,
        ]);

        return [$booking, $conversation, $client, $talent, $profile];
    }

    // ── GET /admin/disputes/{booking}/messages ────────────────────────────────

    public function test_admin_can_access_disputed_booking_messages(): void
    {
        Event::fake([AdminAccessedMessages::class]);

        [$booking, $conversation, $client] = $this->makeDisputedBookingWithConversation();
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->getJson("/api/v1/admin/disputes/{$booking->id}/messages");

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));

        Event::assertDispatched(AdminAccessedMessages::class, fn ($e) =>
            $e->admin->id === $admin->id && $e->booking->id === $booking->id
        );
    }

    public function test_non_disputed_booking_returns_422(): void
    {
        $admin   = $this->makeAdmin();
        $client  = $this->makeClient();
        $profile = TalentProfile::factory()->create();
        $booking = BookingRequest::factory()->create([
            'client_id'         => $client->id,
            'talent_profile_id' => $profile->id,
            'status'            => BookingStatus::Paid,
        ]);

        $this->actingAs($admin)->getJson("/api/v1/admin/disputes/{$booking->id}/messages")
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'BOOKING_NOT_DISPUTED');
    }

    public function test_booking_without_conversation_returns_404(): void
    {
        Event::fake([AdminAccessedMessages::class]);

        $admin   = $this->makeAdmin();
        $client  = $this->makeClient();
        $profile = TalentProfile::factory()->create();
        $booking = BookingRequest::factory()->create([
            'client_id'         => $client->id,
            'talent_profile_id' => $profile->id,
            'status'            => BookingStatus::Disputed,
        ]);

        $this->actingAs($admin)->getJson("/api/v1/admin/disputes/{$booking->id}/messages")
            ->assertNotFound();
    }

    public function test_non_admin_receives_403(): void
    {
        [$booking] = $this->makeDisputedBookingWithConversation();
        $client    = $this->makeClient();

        $this->actingAs($client)->getJson("/api/v1/admin/disputes/{$booking->id}/messages")
            ->assertForbidden();
    }

    public function test_unauthenticated_receives_401(): void
    {
        [$booking] = $this->makeDisputedBookingWithConversation();

        $this->getJson("/api/v1/admin/disputes/{$booking->id}/messages")
            ->assertUnauthorized();
    }
}
