<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ManagerInvitationStatus;
use App\Jobs\SendPushNotification;
use App\Models\ManagerInvitation;
use App\Models\TalentProfile;
use App\Models\User;
use App\Notifications\ManagerInvitedNotification;
use App\Notifications\ManagerInvitationResponseNotification;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ManagerInvitationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function talentWithProfile(): User
    {
        $talent = User::factory()->create();
        $talent->assignRole('talent');
        TalentProfile::factory()->create(['user_id' => $talent->id]);
        return $talent;
    }

    private function managerUser(): User
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        return $manager;
    }

    /** Talent peut inviter un manager par email */
    public function test_talent_can_invite_manager(): void
    {
        Notification::fake();
        $talent = $this->talentWithProfile();

        $response = $this->actingAs($talent, 'sanctum')
            ->postJson('/api/v1/manager/invite', ['email' => 'test@example.com']);

        $response->assertStatus(201);
        $this->assertDatabaseHas('manager_invitations', [
            'talent_profile_id' => $talent->talentProfile->id,
            'manager_email'     => 'test@example.com',
            'status'            => 'pending',
        ]);
    }

    /** Double invitation rejetée (409) */
    public function test_double_invitation_rejected(): void
    {
        Notification::fake();
        $talent = $this->talentWithProfile();

        $this->actingAs($talent, 'sanctum')
            ->postJson('/api/v1/manager/invite', ['email' => 'test@example.com']);

        $response = $this->actingAs($talent, 'sanctum')
            ->postJson('/api/v1/manager/invite', ['email' => 'test@example.com']);

        $response->assertStatus(409);
    }

    /** Manager reçoit la notification (Notification::fake) */
    public function test_manager_receives_email_notification(): void
    {
        Notification::fake();
        $talent = $this->talentWithProfile();

        $this->actingAs($talent, 'sanctum')
            ->postJson('/api/v1/manager/invite', ['email' => 'test@example.com']);

        // The notification is sent to the invitation (notifiable) object
        Notification::assertSentOnDemand(ManagerInvitedNotification::class);
    }

    /** Manager peut accepter avec commentaire → pivot créé */
    public function test_manager_can_accept_invitation(): void
    {
        Queue::fake();
        Notification::fake();
        $talent = $this->talentWithProfile();
        $manager = $this->managerUser();

        $invitation = ManagerInvitation::create([
            'talent_profile_id' => $talent->talentProfile->id,
            'manager_email'     => $manager->email,
            'manager_id'        => $manager->id,
            'status'            => ManagerInvitationStatus::Pending,
            'token'             => \Illuminate\Support\Str::uuid()->toString(),
            'invited_at'        => now(),
        ]);

        $response = $this->actingAs($manager, 'sanctum')
            ->postJson("/api/v1/manager/invitations/{$invitation->id}/accept", [
                'comment' => 'Avec plaisir !',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('manager_invitations', [
            'id'              => $invitation->id,
            'status'          => 'accepted',
            'manager_comment' => 'Avec plaisir !',
        ]);
        // Pivot created
        $this->assertDatabaseHas('talent_manager', [
            'talent_profile_id' => $talent->talentProfile->id,
            'manager_id'        => $manager->id,
        ]);
    }

    /** Manager peut refuser avec commentaire → pivot pas créé */
    public function test_manager_can_reject_invitation(): void
    {
        Queue::fake();
        Notification::fake();
        $talent = $this->talentWithProfile();
        $manager = $this->managerUser();

        $invitation = ManagerInvitation::create([
            'talent_profile_id' => $talent->talentProfile->id,
            'manager_email'     => $manager->email,
            'manager_id'        => $manager->id,
            'status'            => ManagerInvitationStatus::Pending,
            'token'             => \Illuminate\Support\Str::uuid()->toString(),
            'invited_at'        => now(),
        ]);

        $response = $this->actingAs($manager, 'sanctum')
            ->postJson("/api/v1/manager/invitations/{$invitation->id}/reject", [
                'comment' => 'Non disponible.',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('manager_invitations', [
            'id'     => $invitation->id,
            'status' => 'rejected',
        ]);
        // Pivot NOT created
        $this->assertDatabaseMissing('talent_manager', [
            'talent_profile_id' => $talent->talentProfile->id,
            'manager_id'        => $manager->id,
        ]);
    }

    /** Talent notifié à l'acceptation (Queue::fake → SendPushNotification) */
    public function test_talent_notified_on_acceptance(): void
    {
        Queue::fake();
        Notification::fake();
        $talent = $this->talentWithProfile();
        $manager = $this->managerUser();

        $invitation = ManagerInvitation::create([
            'talent_profile_id' => $talent->talentProfile->id,
            'manager_email'     => $manager->email,
            'manager_id'        => $manager->id,
            'status'            => ManagerInvitationStatus::Pending,
            'token'             => \Illuminate\Support\Str::uuid()->toString(),
            'invited_at'        => now(),
        ]);

        $this->actingAs($manager, 'sanctum')
            ->postJson("/api/v1/manager/invitations/{$invitation->id}/accept", ['comment' => null]);

        Queue::assertPushed(SendPushNotification::class, fn ($job) => $job->userId === $talent->id);
        Notification::assertSentTo($talent, ManagerInvitationResponseNotification::class);
    }

    /** Talent notifié au refus */
    public function test_talent_notified_on_rejection(): void
    {
        Queue::fake();
        Notification::fake();
        $talent = $this->talentWithProfile();
        $manager = $this->managerUser();

        $invitation = ManagerInvitation::create([
            'talent_profile_id' => $talent->talentProfile->id,
            'manager_email'     => $manager->email,
            'manager_id'        => $manager->id,
            'status'            => ManagerInvitationStatus::Pending,
            'token'             => \Illuminate\Support\Str::uuid()->toString(),
            'invited_at'        => now(),
        ]);

        $this->actingAs($manager, 'sanctum')
            ->postJson("/api/v1/manager/invitations/{$invitation->id}/reject", ['comment' => 'Non.']);

        Queue::assertPushed(SendPushNotification::class, fn ($job) => $job->userId === $talent->id);
        Notification::assertSentTo($talent, ManagerInvitationResponseNotification::class);
    }

    /** Manager peut lister ses invitations pending */
    public function test_manager_can_list_pending_invitations(): void
    {
        $talent = $this->talentWithProfile();
        $manager = $this->managerUser();

        ManagerInvitation::create([
            'talent_profile_id' => $talent->talentProfile->id,
            'manager_email'     => $manager->email,
            'manager_id'        => $manager->id,
            'status'            => ManagerInvitationStatus::Pending,
            'token'             => \Illuminate\Support\Str::uuid()->toString(),
            'invited_at'        => now(),
        ]);

        $response = $this->actingAs($manager, 'sanctum')
            ->getJson('/api/v1/manager/invitations');

        $response->assertOk()
            ->assertJsonPath('data.invitations.0.manager_email', $manager->email);
    }

    /** Token invalide → 404 sur la route web */
    public function test_invalid_token_returns_404(): void
    {
        $response = $this->get('/manager/invitations/invalid-token/respond');
        $response->assertStatus(404);
    }
}
