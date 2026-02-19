<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Events\AdminAccessedMessages;
use App\Http\Resources\MessageResource;
use App\Models\BookingRequest;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDisputeController extends BaseController
{
    /**
     * GET /api/v1/admin/disputes/{booking}/messages
     *
     * Returns all messages in the conversation associated with a disputed booking.
     * Restricted to admins. Fires an audit event.
     */
    public function messages(Request $request, BookingRequest $booking): JsonResponse
    {
        // The booking must be in disputed (or recently resolved from disputed) status
        if ($booking->status !== BookingStatus::Disputed) {
            return $this->errorResponse(
                'BOOKING_NOT_DISPUTED',
                'Ce dossier n\'est pas en litige.',
                422,
            );
        }

        // Find conversation linked to this booking
        $conversation = Conversation::where('booking_request_id', $booking->id)->first();

        if (! $conversation) {
            return $this->errorResponse(
                'CONVERSATION_NOT_FOUND',
                'Aucune conversation trouvée pour cette réservation.',
                404,
            );
        }

        // Audit log — fired synchronously so it's always recorded before the response
        event(new AdminAccessedMessages($request->user(), $booking));

        $messages = $conversation->messages()->with('sender')->get();

        return $this->successResponse(MessageResource::collection($messages));
    }
}
