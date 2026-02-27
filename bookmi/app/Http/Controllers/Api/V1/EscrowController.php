<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\BookingRequest;
use App\Services\EscrowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EscrowController extends BaseController
{
    public function __construct(
        private readonly EscrowService $escrowService,
    ) {
    }

    /**
     * POST /v1/booking_requests/{booking}/confirm_delivery
     *
     * Client confirms that the service has been delivered.
     * Triggers manual escrow release and booking transition to Confirmed.
     */
    public function confirmDelivery(Request $request, BookingRequest $booking): JsonResponse
    {
        $this->escrowService->confirmDelivery($booking, $request->user());

        return response()->json([
            'message'        => 'Livraison confirmée. Le séquestre a été libéré.',
            'booking_status' => $booking->fresh()->status->value,
        ]);
    }

    /**
     * POST /v1/booking_requests/{booking}/talent_confirm
     *
     * Talent confirms delivery as a fallback when the client has not confirmed
     * within 24 hours of the event date.
     */
    public function talentConfirm(Request $request, BookingRequest $booking): JsonResponse
    {
        $this->escrowService->talentConfirmDelivery($booking, $request->user());

        $fresh = $booking->fresh();

        return response()->json([
            'message'        => 'Prestation marquée comme terminée. Le séquestre a été libéré.',
            'booking_status' => $fresh?->status instanceof \App\Enums\BookingStatus ? $fresh->status->value : '',
        ]);
    }
}
