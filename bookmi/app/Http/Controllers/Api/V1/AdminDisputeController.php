<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Events\AdminAccessedMessages;
use App\Http\Resources\MessageResource;
use App\Models\BookingRequest;
use App\Models\Conversation;
use App\Services\AdminService;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDisputeController extends BaseController
{
    public function __construct(
        private readonly AdminService $admin,
        private readonly AuditService $audit,
    ) {}

    /**
     * GET /api/v1/admin/disputes
     * List all disputed bookings.
     */
    public function index(): JsonResponse
    {
        $disputes = BookingRequest::where('status', BookingStatus::Disputed)
            ->with(['client:id,first_name,last_name,email', 'talentProfile:id,stage_name,user_id'])
            ->latest()
            ->paginate(20);

        return $this->paginatedResponse($disputes);
    }

    /**
     * GET /api/v1/admin/disputes/{booking}
     * Full dispute detail.
     */
    public function show(BookingRequest $booking): JsonResponse
    {
        if ($booking->status !== BookingStatus::Disputed) {
            return $this->errorResponse('BOOKING_NOT_DISPUTED', 'Ce dossier n\'est pas en litige.', 422);
        }

        $booking->load([
            'client:id,first_name,last_name,email,phone',
            'talentProfile:id,stage_name,user_id',
            'talentProfile.user:id,first_name,last_name,email',
            'escrowHold',
            'trackingEvents',
        ]);

        return $this->successResponse($booking);
    }

    /**
     * GET /api/v1/admin/disputes/{booking}/messages
     * Returns all messages in the conversation associated with a disputed booking.
     */
    public function messages(Request $request, BookingRequest $booking): JsonResponse
    {
        if ($booking->status !== BookingStatus::Disputed) {
            return $this->errorResponse('BOOKING_NOT_DISPUTED', 'Ce dossier n\'est pas en litige.', 422);
        }

        $conversation = Conversation::where('booking_request_id', $booking->id)->first();

        if (! $conversation) {
            return $this->errorResponse('CONVERSATION_NOT_FOUND', 'Aucune conversation trouvée pour cette réservation.', 404);
        }

        event(new AdminAccessedMessages($request->user(), $booking));

        $messages = $conversation->messages()->with('sender')->get();

        return $this->successResponse(MessageResource::collection($messages));
    }

    /**
     * POST /api/v1/admin/disputes/{booking}/notes
     * Add an internal note to a dispute.
     */
    public function addNote(Request $request, BookingRequest $booking): JsonResponse
    {
        $data = $request->validate(['note' => 'required|string|max:2000']);

        $this->audit->log('dispute.note_added', $booking, [
            'note'     => $data['note'],
            'admin_id' => $request->user()->id,
        ]);

        return $this->successResponse(['message' => 'Note ajoutée.']);
    }

    /**
     * POST /api/v1/admin/disputes/{booking}/resolve
     * Resolve a dispute: refund_client | pay_talent | compromise
     */
    public function resolve(Request $request, BookingRequest $booking): JsonResponse
    {
        $data = $request->validate([
            'resolution' => 'required|in:refund_client,pay_talent,compromise',
            'note'       => 'nullable|string|max:2000',
        ]);

        $this->admin->resolveDispute($request->user(), $booking, $data['resolution'], $data['note'] ?? null);

        return $this->successResponse(['message' => 'Litige résolu.']);
    }
}
