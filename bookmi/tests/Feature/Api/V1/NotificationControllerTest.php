<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRole;
use App\Jobs\SendPushNotification;
use App\Models\PushNotification;
use App\Models\TalentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([ThrottleRequests::class, ThrottleRequestsWithRedis::class]);
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    }

    private function makeUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole(UserRole::CLIENT->value);
        return $user;
    }

    // ── PUT /me/fcm_token ─────────────────────────────────────────────────────

    public function test_user_can_register_fcm_token(): void
    {
        $user = $this->makeUser();
        $token = 'fcm_token_abc123xyz_example_' . str_repeat('a', 30);

        $response = $this->actingAs($user)->putJson('/api/v1/me/fcm_token', [
            'fcm_token' => $token,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'fcm_token' => $token]);
    }

    public function test_fcm_token_requires_minimum_length(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->putJson('/api/v1/me/fcm_token', [
            'fcm_token' => 'short',
        ])->assertUnprocessable();
    }

    public function test_update_fcm_token_requires_auth(): void
    {
        $this->putJson('/api/v1/me/fcm_token', ['fcm_token' => str_repeat('a', 50)])
            ->assertUnauthorized();
    }

    // ── GET /notifications ────────────────────────────────────────────────────

    public function test_user_sees_only_own_notifications(): void
    {
        $user  = $this->makeUser();
        $other = $this->makeUser();

        PushNotification::factory()->count(3)->create(['user_id' => $user->id]);
        PushNotification::factory()->count(2)->create(['user_id' => $other->id]);

        $response = $this->actingAs($user)->getJson('/api/v1/notifications');

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    public function test_list_notifications_requires_auth(): void
    {
        $this->getJson('/api/v1/notifications')->assertUnauthorized();
    }

    // ── POST /notifications/{notification}/read ───────────────────────────────

    public function test_user_can_mark_notification_as_read(): void
    {
        $user         = $this->makeUser();
        $notification = PushNotification::factory()->create(['user_id' => $user->id, 'read_at' => null]);

        $response = $this->actingAs($user)->postJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertOk();
        $response->assertJsonPath('data.is_read', true);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_mark_others_notification_as_read(): void
    {
        $user  = $this->makeUser();
        $other = $this->makeUser();
        $notif = PushNotification::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)->postJson("/api/v1/notifications/{$notif->id}/read")
            ->assertForbidden();
    }

    // ── POST /notifications/read-all ──────────────────────────────────────────

    public function test_mark_all_read_marks_all_unread_notifications(): void
    {
        $user = $this->makeUser();
        PushNotification::factory()->count(4)->create(['user_id' => $user->id, 'read_at' => null]);
        PushNotification::factory()->create(['user_id' => $user->id, 'read_at' => now()]);

        $response = $this->actingAs($user)->postJson('/api/v1/notifications/read-all');

        $response->assertOk();
        $response->assertJsonPath('data.marked_read', 4);
    }

    // ── SendPushNotification job dispatched on message ─────────────────────────

    public function test_push_notification_job_dispatched_when_message_sent(): void
    {
        Queue::fake();

        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

        $talentUser = User::factory()->create();
        $talentUser->assignRole(UserRole::TALENT->value);
        $talentProfile = TalentProfile::factory()->create(['user_id' => $talentUser->id]);

        $client = $this->makeUser();

        $this->actingAs($client)->postJson('/api/v1/conversations', [
            'talent_profile_id' => $talentProfile->id,
            'message'           => 'Bonjour !',
        ])->assertCreated();

        Queue::assertPushed(
            SendPushNotification::class,
            fn ($job) =>
            $job->userId === $talentUser->id && str_contains($job->title, 'message')
        );
    }
}
