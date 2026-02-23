<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRole;
use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\TalentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MessageControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([ThrottleRequests::class, ThrottleRequestsWithRedis::class]);
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function makeClient(): User
    {
        $user = User::factory()->create();
        $user->assignRole(UserRole::CLIENT->value);
        return $user;
    }

    private function makeTalentWithProfile(): array
    {
        $talentUser = User::factory()->create();
        $talentUser->assignRole(UserRole::TALENT->value);
        $talentProfile = TalentProfile::factory()->create(['user_id' => $talentUser->id]);
        return [$talentUser, $talentProfile];
    }

    private function makeConversation(User $client, TalentProfile $talentProfile): Conversation
    {
        return Conversation::factory()->create([
            'client_id'        => $client->id,
            'talent_profile_id' => $talentProfile->id,
        ]);
    }

    // ── GET /conversations ─────────────────────────────────────────────────────

    public function test_list_conversations_returns_own_conversations(): void
    {
        [$talentUser, $talentProfile] = $this->makeTalentWithProfile();
        $client = $this->makeClient();
        $this->makeConversation($client, $talentProfile);

        // Another conversation unrelated to client
        $otherClient = $this->makeClient();
        $this->makeConversation($otherClient, $talentProfile);

        $response = $this->actingAs($client)->getJson('/api/v1/conversations');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_list_conversations_requires_authentication(): void
    {
        $this->getJson('/api/v1/conversations')->assertUnauthorized();
    }

    public function test_talent_sees_their_own_conversations(): void
    {
        [$talentUser, $talentProfile] = $this->makeTalentWithProfile();
        $client1 = $this->makeClient();
        $client2 = $this->makeClient();
        $this->makeConversation($client1, $talentProfile);
        $this->makeConversation($client2, $talentProfile);

        $response = $this->actingAs($talentUser)->getJson('/api/v1/conversations');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    // ── POST /conversations ────────────────────────────────────────────────────

    public function test_client_can_start_conversation_and_send_first_message(): void
    {
        Event::fake([MessageSent::class]);

        [$talentUser, $talentProfile] = $this->makeTalentWithProfile();
        $client = $this->makeClient();

        $response = $this->actingAs($client)->postJson('/api/v1/conversations', [
            'talent_profile_id' => $talentProfile->id,
            'message'           => 'Bonjour, je souhaite vous contacter.',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.message.content', 'Bonjour, je souhaite vous contacter.');
        $response->assertJsonPath('data.message.sender_id', $client->id);

        $this->assertDatabaseHas('conversations', [
            'client_id'        => $client->id,
            'talent_profile_id' => $talentProfile->id,
        ]);
        $this->assertDatabaseHas('messages', [
            'content' => 'Bonjour, je souhaite vous contacter.',
        ]);

        Event::assertDispatched(MessageSent::class);
    }

    public function test_starting_conversation_twice_reuses_existing(): void
    {
        Event::fake([MessageSent::class]);

        [$talentUser, $talentProfile] = $this->makeTalentWithProfile();
        $client = $this->makeClient();

        $this->actingAs($client)->postJson('/api/v1/conversations', [
            'talent_profile_id' => $talentProfile->id,
            'message'           => 'Premier message.',
        ])->assertCreated();

        $this->actingAs($client)->postJson('/api/v1/conversations', [
            'talent_profile_id' => $talentProfile->id,
            'message'           => 'Deuxième message.',
        ])->assertCreated();

        $this->assertDatabaseCount('conversations', 1);
        $this->assertDatabaseCount('messages', 2);
    }

    public function test_talent_cannot_start_conversation(): void
    {
        [$talentUser, $talentProfile] = $this->makeTalentWithProfile();
        [$otherTalentUser, $otherTalentProfile] = $this->makeTalentWithProfile();

        $response = $this->actingAs($talentUser)->postJson('/api/v1/conversations', [
            'talent_profile_id' => $otherTalentProfile->id,
            'message'           => 'Test.',
        ]);

        $response->assertForbidden();
    }

    public function test_start_conversation_requires_valid_talent_profile(): void
    {
        $client = $this->makeClient();

        $this->actingAs($client)->postJson('/api/v1/conversations', [
            'talent_profile_id' => 99999,
            'message'           => 'Test.',
        ])->assertUnprocessable();
    }

    // ── GET /conversations/{conversation}/messages ─────────────────────────────

    public function test_participant_can_fetch_messages(): void
    {
        [$talentUser, $talentProfile] = $this->makeTalentWithProfile();
        $client = $this->makeClient();
        $conversation = $this->makeConversation($client, $talentProfile);

        Message::factory()->count(3)->create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $client->id,
        ]);

        $response = $this->actingAs($client)->getJson("/api/v1/conversations/{$conversation->id}/messages");

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
        $this->assertArrayHasKey('meta', $response->json());
    }

    public function test_non_participant_cannot_fetch_messages(): void
    {
        [$talentUser, $talentProfile] = $this->makeTalentWithProfile();
        $client = $this->makeClient();
        $conversation = $this->makeConversation($client, $talentProfile);

        $stranger = $this->makeClient();

        $this->actingAs($stranger)->getJson("/api/v1/conversations/{$conversation->id}/messages")
            ->assertForbidden();
    }

    // ── POST /conversations/{conversation}/messages ────────────────────────────

    public function test_participant_can_send_message(): void
    {
        Event::fake([MessageSent::class]);

        [$talentUser, $talentProfile] = $this->makeTalentWithProfile();
        $client = $this->makeClient();
        $conversation = $this->makeConversation($client, $talentProfile);

        $response = $this->actingAs($talentUser)->postJson("/api/v1/conversations/{$conversation->id}/messages", [
            'content' => 'Bonsoir, voici mes disponibilités.',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.sender_id', $talentUser->id);
        $response->assertJsonPath('data.content', 'Bonsoir, voici mes disponibilités.');

        Event::assertDispatched(
            MessageSent::class,
            fn (MessageSent $e) =>
            $e->message->conversation_id === $conversation->id
        );
    }

    public function test_send_message_validates_content_required(): void
    {
        [$talentUser, $talentProfile] = $this->makeTalentWithProfile();
        $client = $this->makeClient();
        $conversation = $this->makeConversation($client, $talentProfile);

        $this->actingAs($client)->postJson("/api/v1/conversations/{$conversation->id}/messages", [
            'content' => '',
        ])->assertUnprocessable();
    }

    public function test_non_participant_cannot_send_message(): void
    {
        [$talentUser, $talentProfile] = $this->makeTalentWithProfile();
        $client = $this->makeClient();
        $conversation = $this->makeConversation($client, $talentProfile);
        $stranger = $this->makeClient();

        $this->actingAs($stranger)->postJson("/api/v1/conversations/{$conversation->id}/messages", [
            'content' => 'Message non autorisé.',
        ])->assertForbidden();
    }

    // ── POST /conversations/{conversation}/read ────────────────────────────────

    public function test_mark_as_read_marks_others_messages(): void
    {
        [$talentUser, $talentProfile] = $this->makeTalentWithProfile();
        $client = $this->makeClient();
        $conversation = $this->makeConversation($client, $talentProfile);

        // 3 unread messages from talent
        Message::factory()->count(3)->create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $talentUser->id,
            'read_at'         => null,
        ]);

        $response = $this->actingAs($client)->postJson("/api/v1/conversations/{$conversation->id}/read");

        $response->assertOk();
        $response->assertJsonPath('data.marked_read', 3);
    }

    // ── Anti-désintermédiation (Story 5.2) ────────────────────────────────────

    public function test_message_with_phone_number_is_flagged_with_warning(): void
    {
        Event::fake([MessageSent::class]);

        [$talentUser, $talentProfile] = $this->makeTalentWithProfile();
        $client = $this->makeClient();
        $conversation = $this->makeConversation($client, $talentProfile);

        $response = $this->actingAs($client)->postJson("/api/v1/conversations/{$conversation->id}/messages", [
            'content' => 'Contactez-moi au +225 07 12 34 56 78 pour plus d\'infos.',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.is_flagged', true);
        $response->assertJsonStructure(['warning' => ['code', 'message']]);
        $response->assertJsonPath('warning.code', 'CONTACT_SHARING_DETECTED');

        $this->assertDatabaseHas('messages', ['is_flagged' => true]);
    }

    public function test_clean_message_has_no_warning(): void
    {
        Event::fake([MessageSent::class]);

        [$talentUser, $talentProfile] = $this->makeTalentWithProfile();
        $client = $this->makeClient();
        $conversation = $this->makeConversation($client, $talentProfile);

        $response = $this->actingAs($client)->postJson("/api/v1/conversations/{$conversation->id}/messages", [
            'content' => 'Bonjour, êtes-vous disponible le 15 mars ?',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.is_flagged', false);
        $this->assertArrayNotHasKey('warning', $response->json());
    }

    public function test_mark_as_read_does_not_mark_own_messages(): void
    {
        [$talentUser, $talentProfile] = $this->makeTalentWithProfile();
        $client = $this->makeClient();
        $conversation = $this->makeConversation($client, $talentProfile);

        // Client's own messages
        Message::factory()->count(2)->create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $client->id,
            'read_at'         => null,
        ]);

        $response = $this->actingAs($client)->postJson("/api/v1/conversations/{$conversation->id}/read");

        $response->assertOk();
        $response->assertJsonPath('data.marked_read', 0);
    }
}
