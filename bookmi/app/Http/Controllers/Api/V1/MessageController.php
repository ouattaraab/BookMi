<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\SendMessageRequest;
use App\Http\Requests\Api\StartConversationRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\TalentProfile;
use App\Models\User;
use App\Services\MessagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class MessageController extends BaseController
{
    public function __construct(
        private readonly MessagingService $messagingService,
    ) {
    }

    /**
     * GET /api/v1/conversations
     * List all conversations for the authenticated user.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $conversations = $this->messagingService->listConversations($request->user());

        return ConversationResource::collection($conversations);
    }

    /**
     * POST /api/v1/conversations
     * Start a new conversation (or retrieve existing) and send the first message.
     * Both clients and talents (via booking_request_id) can initiate.
     */
    public function store(StartConversationRequest $request): JsonResponse
    {
        $user = $request->user();
        $bookingRequestId = $request->integer('booking_request_id') ?: null;

        // Determine client and talentProfileId from context
        if ($user->hasRole(\App\Enums\UserRole::CLIENT->value)) {
            // Standard client flow
            $clientUser      = $user;
            $talentProfileId = $request->integer('talent_profile_id');
        } elseif ($bookingRequestId !== null) {
            // Talent flow: look up booking to get client + talent_profile_id
            $booking = \App\Models\BookingRequest::find($bookingRequestId);
            if (! $booking) {
                return response()->json(['error' => ['code' => 'NOT_FOUND', 'message' => 'Réservation introuvable.']], 404);
            }
            // Verify caller is the talent for this booking
            $isTalent = TalentProfile::where('id', $booking->talent_profile_id)
                ->where('user_id', $user->id)
                ->exists();
            if (! $isTalent) {
                return response()->json(['error' => ['code' => 'FORBIDDEN', 'message' => 'Accès refusé.']], 403);
            }
            $clientUser      = User::findOrFail($booking->client_id);
            $talentProfileId = $booking->talent_profile_id;
        } else {
            return response()->json([
                'error' => ['code' => 'FORBIDDEN', 'message' => 'Seuls les clients peuvent initier une conversation sans réservation.'],
            ], 403);
        }

        $messageText = $request->filled('message') ? $request->string('message')->toString() : null;

        [$conversation, $message] = DB::transaction(function () use ($clientUser, $talentProfileId, $bookingRequestId, $user, $messageText) {
            $conversation = $this->messagingService->getOrCreateConversation(
                client: $clientUser,
                talentProfileId: $talentProfileId,
                bookingRequestId: $bookingRequestId,
            );

            $msg = null;
            if ($messageText !== null) {
                $msg = $this->messagingService->sendMessage(
                    conversation: $conversation,
                    sender: $user,
                    content: $messageText,
                );
            }

            return [$conversation, $msg];
        });

        // When no message was provided, just return the conversation ID so the
        // client can navigate to the chat screen.
        if ($message === null) {
            return response()->json([
                'data' => new ConversationResource($conversation->load(['client', 'talentProfile.user', 'latestMessage'])),
            ], 201);
        }

        $responseData = [
            'data' => [
                'conversation' => new ConversationResource($conversation->load(['client', 'talentProfile.user', 'latestMessage'])),
                'message'      => new MessageResource($message->load('sender')),
            ],
        ];

        if ($message->is_flagged) {
            $responseData['warning'] = [
                'code'    => 'CONTACT_SHARING_DETECTED',
                'message' => 'Votre message contient des informations de contact. '
                    . 'Partager des coordonnées en dehors de BookMi est interdit et peut entraîner une suspension de compte.',
            ];
        }

        return response()->json($responseData, 201);
    }

    /**
     * GET /api/v1/conversations/{conversation}/messages
     * Paginated message history for a conversation.
     */
    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeParticipant($conversation, $request);

        $paginated = $this->messagingService->getMessages($conversation);

        return response()->json([
            'data' => MessageResource::collection($paginated->items()),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    /**
     * POST /api/v1/conversations/{conversation}/messages
     * Send a message in an existing conversation.
     */
    public function send(SendMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeParticipant($conversation, $request);

        $type = \App\Enums\MessageType::from($request->input('type', 'text'));

        $message = $this->messagingService->sendMessage(
            conversation: $conversation,
            sender: $request->user(),
            content: $request->input('content') ?? '',
            type: $type,
            mediaFile: $request->file('file'),
        );

        $response = ['data' => new MessageResource($message->load('sender'))];

        if ($message->is_flagged) {
            $response['warning'] = [
                'code'    => 'CONTACT_SHARING_DETECTED',
                'message' => 'Votre message contient des informations de contact. '
                    . 'Partager des coordonnées en dehors de BookMi est interdit et peut entraîner une suspension de compte.',
            ];
        }

        return response()->json($response, 201);
    }

    /**
     * POST /api/v1/conversations/{conversation}/read
     * Mark all messages in the conversation as read for the current user.
     */
    public function read(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeParticipant($conversation, $request);

        $count = $this->messagingService->markAsRead($conversation, $request->user());

        return response()->json(['data' => ['marked_read' => $count]]);
    }

    /**
     * DELETE /api/v1/conversations/{conversation}
     * Delete an entire conversation (only for participants).
     */
    public function destroyConversation(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeParticipant($conversation, $request);
        $conversation->messages()->delete();
        $conversation->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    /**
     * DELETE /api/v1/conversations/{conversation}/messages/{message}
     * Delete a single message (only the sender can delete their own).
     */
    public function destroyMessage(Request $request, Conversation $conversation, Message $message): JsonResponse
    {
        $this->authorizeParticipant($conversation, $request);

        if ($message->conversation_id !== $conversation->id) {
            abort(404);
        }

        if ($message->sender_id !== $request->user()->id) {
            abort(403, 'Vous ne pouvez supprimer que vos propres messages.');
        }

        $message->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    private function authorizeParticipant(Conversation $conversation, Request $request): void
    {
        if (! $conversation->isParticipant($request->user())) {
            abort(403, 'You are not a participant of this conversation.');
        }
    }
}
