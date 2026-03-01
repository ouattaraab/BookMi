<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRole;
use App\Events\BookingCreated;
use App\Events\MessageSent;
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

class AutoReplyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([ThrottleRequests::class, ThrottleRequestsWithRedis::class]);
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    }

    private function makeClient(): User
    {
        $user = User::factory()->create();
        $user->assignRole(UserRole::CLIENT->value);
        return $user;
    }

    private function makeTalentWithProfile(bool $autoReplyActive = false, ?string $autoReplyMessage = null): array
    {
        $talentUser = User::factory()->create();
        $talentUser->assignRole(UserRole::TALENT->value);
        $talentProfile = TalentProfile::factory()->create([
            'user_id'              => $talentUser->id,
            'auto_reply_is_active' => $autoReplyActive,
            'auto_reply_message'   => $autoReplyMessage,
        ]);
        return [$talentUser, $talentProfile];
    }

    // ── PUT /talent_profiles/me/auto_reply ────────────────────────────────────

    public function test_talent_can_enable_auto_reply(): void
    {
        [$talentUser, $talentProfile] = $this->makeTalentWithProfile();

        $response = $this->actingAs($talentUser)->putJson('/api/v1/talent_profiles/me/auto_reply', [
            'auto_reply_message'   => 'Merci de votre message ! Je vous réponds sous 24h.',
            'auto_reply_is_active' => true,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.auto_reply_is_active', true);
        $response->assertJsonPath('data.auto_reply_message', 'Merci de votre message ! Je vous réponds sous 24h.');

        $this->assertDatabaseHas('talent_profiles', [
            'id'                   => $talentProfile->id,
            'auto_reply_is_active' => true,
        ]);
    }

    public function test_talent_can_disable_auto_reply(): void
    {
        [$talentUser, $talentProfile] = $this->makeTalentWithProfile(true, 'Message auto');

        $this->actingAs($talentUser)->putJson('/api/v1/talent_profiles/me/auto_reply', [
            'auto_reply_message'   => 'Message auto',
            'auto_reply_is_active' => false,
        ])->assertOk();

        $this->assertDatabaseHas('talent_profiles', [
            'id'                   => $talentProfile->id,
            'auto_reply_is_active' => false,
        ]);
    }

    public function test_update_auto_reply_requires_message(): void
    {
        [$talentUser] = $this->makeTalentWithProfile();

        $this->actingAs($talentUser)->putJson('/api/v1/talent_profiles/me/auto_reply', [
            'auto_reply_is_active' => true,
        ])->assertUnprocessable();
    }

    public function test_update_auto_reply_requires_authentication(): void
    {
        $this->putJson('/api/v1/talent_profiles/me/auto_reply', [
            'auto_reply_message'   => 'test',
            'auto_reply_is_active' => true,
        ])->assertUnauthorized();
    }

    // ── Auto-reply triggered on first message ──────────────────────────────────

    public function test_auto_reply_sent_when_client_sends_first_message(): void
    {
        Event::fake([MessageSent::class]);

        [$talentUser, $talentProfile] = $this->makeTalentWithProfile(
            autoReplyActive: true,
            autoReplyMessage: 'Merci pour votre message, je reviens vers vous bientôt !',
        );
        $client = $this->makeClient();

        // Client starts conversation
        $this->actingAs($client)->postJson('/api/v1/conversations', [
            'talent_profile_id' => $talentProfile->id,
            'message'           => 'Bonjour, êtes-vous disponible ?',
        ])->assertCreated();

        // 2 messages should exist: client's + auto-reply
        $this->assertDatabaseCount('messages', 2);

        $autoReply = Message::where('is_auto_reply', true)->first();
        $this->assertNotNull($autoReply);
        $this->assertEquals($talentUser->id, $autoReply->sender_id);
        $this->assertEquals('Merci pour votre message, je reviens vers vous bientôt !', $autoReply->content);
    }

    public function test_auto_reply_not_sent_when_disabled(): void
    {
        Event::fake([MessageSent::class]);

        [$talentUser, $talentProfile] = $this->makeTalentWithProfile(
            autoReplyActive: false,
            autoReplyMessage: 'Message auto désactivé.',
        );
        $client = $this->makeClient();

        $this->actingAs($client)->postJson('/api/v1/conversations', [
            'talent_profile_id' => $talentProfile->id,
            'message'           => 'Bonjour.',
        ])->assertCreated();

        $this->assertDatabaseCount('messages', 1);
        $this->assertDatabaseMissing('messages', ['is_auto_reply' => true]);
    }

    // ── Auto-reply triggered on BookingCreated event ───────────────────────────

    public function test_auto_reply_sent_on_booking_creation_when_enabled(): void
    {
        Event::fake([MessageSent::class]);

        [$talentUser, $talentProfile] = $this->makeTalentWithProfile(
            autoReplyActive: true,
            autoReplyMessage: 'Merci pour votre réservation, je vous confirme très vite !',
        );
        $client = $this->makeClient();

        $booking = BookingRequest::factory()
            ->state(['talent_profile_id' => $talentProfile->id])
            ->create(['client_id' => $client->id]);

        BookingCreated::dispatch($booking);

        // A conversation linked to the booking should have been created
        $this->assertDatabaseHas('conversations', ['booking_request_id' => $booking->id]);

        // Auto-reply message should exist in that conversation
        $autoReply = Message::where('is_auto_reply', true)->first();
        $this->assertNotNull($autoReply);
        $this->assertEquals($talentUser->id, $autoReply->sender_id);
        $this->assertEquals(
            'Merci pour votre réservation, je vous confirme très vite !',
            $autoReply->content,
        );
    }

    public function test_auto_reply_not_sent_on_booking_creation_when_disabled(): void
    {
        Event::fake([MessageSent::class]);

        [$talentUser, $talentProfile] = $this->makeTalentWithProfile(
            autoReplyActive: false,
            autoReplyMessage: 'Message auto désactivé.',
        );
        $client = $this->makeClient();

        $booking = BookingRequest::factory()
            ->state(['talent_profile_id' => $talentProfile->id])
            ->create(['client_id' => $client->id]);

        BookingCreated::dispatch($booking);

        $this->assertDatabaseMissing('conversations', ['booking_request_id' => $booking->id]);
        $this->assertDatabaseMissing('messages', ['is_auto_reply' => true]);
    }

    public function test_auto_reply_sent_only_once_per_conversation(): void
    {
        Event::fake([MessageSent::class]);

        [$talentUser, $talentProfile] = $this->makeTalentWithProfile(
            autoReplyActive: true,
            autoReplyMessage: 'Réponse automatique.',
        );
        $client = $this->makeClient();

        // First message → creates conversation + auto-reply
        $this->actingAs($client)->postJson('/api/v1/conversations', [
            'talent_profile_id' => $talentProfile->id,
            'message'           => 'Premier message.',
        ])->assertCreated();

        $conversation = Conversation::first();

        // Second message in same conversation → no second auto-reply
        $this->actingAs($client)->postJson("/api/v1/conversations/{$conversation->id}/messages", [
            'content' => 'Deuxième message.',
        ])->assertCreated();

        $this->assertDatabaseCount('messages', 3); // 2 client + 1 auto-reply
        $this->assertEquals(1, Message::where('is_auto_reply', true)->count());
    }
}
