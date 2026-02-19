<?php

namespace App\Services;

use App\Enums\MessageType;
use App\Events\MessageSent;
use App\Jobs\SendPushNotification;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\TalentProfile;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class MessagingService
{
    public function __construct(
        private readonly ContactDetectionService $contactDetector,
    ) {
    }
    /**
     * Returns all conversations for the authenticated user,
     * with the latest message eager-loaded, sorted by most recent activity.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Conversation>
     */
    public function listConversations(User $user): Collection
    {
        $talentProfileId = TalentProfile::where('user_id', $user->id)->value('id');

        return Conversation::with(['client', 'talentProfile.user', 'latestMessage'])
            ->where(function ($q) use ($user, $talentProfileId) {
                $q->where('client_id', $user->id);
                if ($talentProfileId) {
                    $q->orWhere('talent_profile_id', $talentProfileId);
                }
            })
            ->orderByDesc('last_message_at')
            ->get();
    }

    /**
     * Gets or creates a conversation between a client and a talent profile.
     * An optional booking_request_id can be attached.
     */
    public function getOrCreateConversation(
        User $client,
        int $talentProfileId,
        ?int $bookingRequestId = null,
    ): Conversation {
        /** @var Conversation $conversation */
        $conversation = Conversation::firstOrCreate(
            [
                'client_id'        => $client->id,
                'talent_profile_id' => $talentProfileId,
            ],
            [
                'booking_request_id' => $bookingRequestId,
                'last_message_at'    => now(),
            ],
        );

        // Attach booking request if not already set
        if ($bookingRequestId && $conversation->booking_request_id === null) {
            $conversation->update(['booking_request_id' => $bookingRequestId]);
        }

        return $conversation;
    }

    /**
     * Returns paginated messages for a conversation.
     *
     * @return LengthAwarePaginator<Message>
     */
    public function getMessages(Conversation $conversation, int $perPage = 30): LengthAwarePaginator
    {
        return $conversation->messages()
            ->with('sender')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Sends a message in a conversation and broadcasts the event.
     */
    public function sendMessage(
        Conversation $conversation,
        User $sender,
        string $content,
        MessageType $type = MessageType::Text,
        bool $isAutoReply = false,
    ): Message {
        $isFlagged = $this->contactDetector->containsContactInfo($content);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $sender->id,
            'content'         => $content,
            'type'            => $type,
            'is_auto_reply'   => $isAutoReply,
            'is_flagged'      => $isFlagged,
        ]);

        $conversation->update(['last_message_at' => now()]);

        // Use broadcast() without toOthers() — the client filters by sender_id.
        // toOthers() requires a socket ID header absent from REST API calls.
        broadcast(new MessageSent($message));

        // Dispatch FCM push notification to the other participant(s)
        $this->notifyRecipients($conversation, $sender, $message);

        // Trigger auto-reply when a client sends the FIRST message in a new conversation
        // and the talent has auto-reply enabled.
        if (! $isAutoReply) {
            $this->maybeAutoReply($conversation, $sender, $message);
        }

        return $message;
    }

    /**
     * Dispatches a push notification to the other participant(s) in the conversation.
     */
    private function notifyRecipients(Conversation $conversation, User $sender, Message $message): void
    {
        // Load conversation participants if not already loaded
        $conversation->loadMissing(['client', 'talentProfile.user']);

        $recipients = [];

        if ($conversation->client && $conversation->client->id !== $sender->id) {
            $recipients[] = $conversation->client;
        }

        $talentUser = $conversation->talentProfile?->user;
        if ($talentUser && $talentUser->id !== $sender->id) {
            $recipients[] = $talentUser;
        }

        $senderName = $sender->first_name ?? 'Quelqu\'un';
        $preview    = mb_substr($message->content, 0, 80);

        foreach ($recipients as $recipient) {
            SendPushNotification::dispatch(
                userId: $recipient->id,
                title: "Nouveau message de {$senderName}",
                body: $preview,
                data: ['conversation_id' => (string) $conversation->id],
            );
        }
    }

    /**
     * Sends a talent's auto-reply if:
     *  1. Auto-reply is active on the talent profile
     *  2. This is the first (and only) message from the client in the conversation
     *     (i.e., no prior auto-reply has been sent yet)
     */
    private function maybeAutoReply(Conversation $conversation, User $sender, Message $triggerMessage): void
    {
        $talentProfile = $conversation->talentProfile ?? $conversation->load('talentProfile')->talentProfile;

        if (! $talentProfile || ! $talentProfile->auto_reply_is_active || ! $talentProfile->auto_reply_message) {
            return;
        }

        // Only auto-reply once per conversation (no prior auto-reply message exists)
        $alreadyReplied = $conversation->messages()
            ->where('is_auto_reply', true)
            ->exists();

        if ($alreadyReplied) {
            return;
        }

        // The auto-reply is sent "from" the talent user
        $talentUser = $talentProfile->user;
        if (! $talentUser) {
            return;
        }

        $this->sendMessage(
            conversation: $conversation,
            sender: $talentUser,
            content: $talentProfile->auto_reply_message,
            isAutoReply: true,
        );
    }

    /**
     * Marks all unread messages in the conversation as read for the given user.
     */
    public function markAsRead(Conversation $conversation, User $reader): int
    {
        return $conversation->messages()
            ->where('sender_id', '!=', $reader->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
