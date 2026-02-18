<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\InitiatePaymentRequest;
use App\Http\Requests\Api\SubmitOtpRequest;
use App\Http\Resources\TransactionResource;
use App\Models\BookingRequest;
use App\Models\Transaction;
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

    /**
     * POST /v1/payments/submit_otp
     *
     * Submit OTP to complete a pending mobile money charge (Paystack send_otp flow).
     * The authenticated user must be the client who initiated the payment.
     */
    public function submitOtp(SubmitOtpRequest $request): JsonResponse
    {
        $transaction = Transaction::where('idempotency_key', $request->validated('reference'))
            ->firstOrFail();

        abort_if(
            $transaction->bookingRequest->client_id !== $request->user()->id,
            403,
            "Vous n'êtes pas autorisé à soumettre un OTP pour cette transaction.",
        );

        $result = $this->paymentService->submitOtp(
            $request->validated('reference'),
            $request->validated('otp'),
        );

        return response()->json([
            'status'       => $result['status'] ?? null,
            'display_text' => $result['display_text'] ?? null,
        ]);
    }
}
