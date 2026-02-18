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
    ) {}

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
}
