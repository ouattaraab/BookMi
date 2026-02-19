<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Requests\Api\SendMessageRequest;
use App\Http\Requests\Api\StartConversationRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\TalentProfile;
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
     */
    public function store(StartConversationRequest $request): JsonResponse
    {
        $user = $request->user();

        // Only clients can start conversations
        if (! $user->hasRole(\App\Enums\UserRole::CLIENT->value)) {
            return response()->json([
                'error' => ['code' => 'FORBIDDEN', 'message' => 'Only clients can start conversations.'],
            ], 403);
        }

        [$conversation, $message] = DB::transaction(function () use ($user, $request) {
            $conversation = $this->messagingService->getOrCreateConversation(
                client: $user,
                talentProfileId: $request->integer('talent_profile_id'),
                bookingRequestId: $request->integer('booking_request_id') ?: null,
            );

            $message = $this->messagingService->sendMessage(
                conversation: $conversation,
                sender: $user,
                content: $request->string('message')->toString(),
            );

            return [$conversation, $message];
        });

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

        $message = $this->messagingService->sendMessage(
            conversation: $conversation,
            sender: $request->user(),
            content: $request->string('content')->toString(),
            type: \App\Enums\MessageType::from($request->input('type', 'text')),
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

    private function authorizeParticipant(Conversation $conversation, Request $request): void
    {
        if (! $conversation->isParticipant($request->user())) {
            abort(403, 'You are not a participant of this conversation.');
        }
    }
}
