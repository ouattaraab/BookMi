<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\InitiatePaymentRequest;
use App\Http\Resources\TransactionResource;
use App\Models\BookingRequest;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

class PaymentController extends BaseController
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    /**
     * POST /v1/payments/initiate
     *
     * Initiate a Mobile Money payment for an accepted booking.
     */
    public function initiate(InitiatePaymentRequest $request): JsonResponse
    {
        $booking = BookingRequest::findOrFail($request->validated('booking_id'));

        abort_if(
            $booking->client_id !== $request->user()->id,
            403,
            "Vous n'êtes pas autorisé à payer cette réservation.",
        );

        $transaction = $this->paymentService->initiatePayment(
            $booking,
            $request->validated(),
        );

        return response()->json(new TransactionResource($transaction), 201);
    }
}
