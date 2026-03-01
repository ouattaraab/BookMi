<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_default_preferences(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/notification_preferences')
            ->assertOk()
            ->assertJsonPath('data.new_message', true)
            ->assertJsonPath('data.booking_updates', true)
            ->assertJsonPath('data.new_review', true)
            ->assertJsonPath('data.follow_update', true)
            ->assertJsonPath('data.admin_broadcast', true);
    }

    public function test_user_can_update_preferences(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/me/notification_preferences', [
                'new_message'     => false,
                'booking_updates' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.new_message', false)
            ->assertJsonPath('data.booking_updates', true);
    }

    public function test_updated_preferences_are_persisted(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/me/notification_preferences', ['follow_update' => false]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/notification_preferences')
            ->assertJsonPath('data.follow_update', false);
    }

    public function test_unauthenticated_user_cannot_access_preferences(): void
    {
        $this->getJson('/api/v1/me/notification_preferences')->assertUnauthorized();
    }
}
