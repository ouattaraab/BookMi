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
use Illuminate\Http\UploadedFile;

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

        $conversations = Conversation::with([
            'client',
            'talentProfile.user',
            'latestMessage',
            'bookingRequest',
        ])
            ->where(function ($q) use ($user, $talentProfileId) {
                $q->where('client_id', $user->id);
                if ($talentProfileId) {
                    $q->orWhere('talent_profile_id', $talentProfileId);
                }
            })
            ->orderByDesc('last_message_at')
            ->get();

        // Attach unread count per conversation (messages from others, not yet read)
        $conversations->each(function (Conversation $conv) use ($user) {
            $conv->unread_count = $conv->messages()
                ->where('sender_id', '!=', $user->id)
                ->whereNull('read_at')
                ->count();
        });

        return $conversations;
    }

    /**
     * Gets or creates a conversation.
     * When a booking_request_id is provided it is the unique key (1 conversation per booking).
     * Without a booking_request_id the legacy (client × talent) pair is used as fallback.
     */
    public function getOrCreateConversation(
        User $client,
        int $talentProfileId,
        ?int $bookingRequestId = null,
    ): Conversation {
        if ($bookingRequestId !== null) {
            /** @var Conversation $conversation */
            $conversation = Conversation::firstOrCreate(
                ['booking_request_id' => $bookingRequestId],
                [
                    'client_id'         => $client->id,
                    'talent_profile_id' => $talentProfileId,
                    'last_message_at'   => now(),
                ],
            );
        } else {
            /** @var Conversation $conversation */
            $conversation = Conversation::firstOrCreate(
                [
                    'client_id'          => $client->id,
                    'talent_profile_id'  => $talentProfileId,
                    'booking_request_id' => null,
                ],
                ['last_message_at' => now()],
            );
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
        ?UploadedFile $mediaFile = null,
    ): Message {
        $isFlagged = $type === MessageType::Text
            ? $this->contactDetector->containsContactInfo($content)
            : false;

        $mediaPath = null;
        $mimeType  = null;

        if ($mediaFile !== null) {
            $dir       = "messages/{$conversation->id}";
            $mediaPath = $mediaFile->store($dir, 'public');
            $mimeType  = $mediaFile->getMimeType();
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $sender->id,
            'content'         => $content,
            'type'            => $type,
            'is_auto_reply'   => $isAutoReply,
            'is_flagged'      => $isFlagged,
            'media_path'      => $mediaPath,
            'media_type'      => $mimeType,
        ]);

        $conversation->update(['last_message_at' => now()]);

        broadcast(new MessageSent($message));

        $this->notifyRecipients($conversation, $sender, $message);

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
        $preview    = $message->type !== MessageType::Text
            ? ($message->type === MessageType::Image ? '📷 Photo' : '🎥 Vidéo')
            : mb_substr($message->content, 0, 80);

        foreach ($recipients as $recipient) {
            SendPushNotification::dispatch(
                userId: $recipient->id,
                title: "Nouveau message de {$senderName}",
                body: $preview,
                data: [
                    'type'            => 'new_message',
                    'conversation_id' => (string) $conversation->id,
                ],
            );
        }
    }

    /**
     * Sends a talent's auto-reply if conditions are met.
     */
    private function maybeAutoReply(Conversation $conversation, User $sender, Message $triggerMessage): void
    {
        $talentProfile = $conversation->talentProfile ?? $conversation->load('talentProfile')->talentProfile;

        if (! $talentProfile || ! $talentProfile->auto_reply_is_active || ! $talentProfile->auto_reply_message) {
            return;
        }

        $alreadyReplied = $conversation->messages()
            ->where('is_auto_reply', true)
            ->exists();

        if ($alreadyReplied) {
            return;
        }

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
