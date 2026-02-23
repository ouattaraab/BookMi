<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\AdminRefundRequest;
use App\Models\BookingRequest;
use App\Services\RefundService;
use Illuminate\Http\JsonResponse;

class AdminRefundController extends BaseController
{
    public function __construct(
        private readonly RefundService $refundService,
    ) {
    }

    /**
     * POST /v1/admin/booking_requests/{booking}/refund
     *
     * Admin-only: process a full or partial refund for a disputed booking.
     * Calls RefundService which handles the 3-step pattern:
     *   1. DB lock + optimistic mark as Refunded.
     *   2. HTTP call to payment gateway (outside DB transaction).
     *   3. Persist refund reference + cascade (escrow → Refunded, booking → Cancelled).
     */
    public function refund(AdminRefundRequest $request, BookingRequest $booking): JsonResponse
    {
        $this->refundService->processRefund(
            $booking,
            $request->validated('amount'),
            $request->validated('reason'),
        );

        return response()->json([
            'message' => 'Remboursement effectué avec succès.',
        ]);
    }
}
