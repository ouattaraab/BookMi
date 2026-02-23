<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\InitiatePaymentRequest;
use App\Http\Requests\Api\SubmitOtpRequest;
use App\Http\Resources\TransactionResource;
use App\Models\BookingRequest;
use App\Models\Transaction;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends BaseController
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {
    }

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

    /**
     * GET /v1/payments/callback
     *
     * Called by Paystack after 3D Secure redirect (card / bank_transfer).
     * The actual state change is handled by the webhook (Story 4.2).
     * This endpoint acknowledges the redirect so the Flutter WebView can detect success.
     */
    public function callback(Request $request): JsonResponse
    {
        $reference = $request->query('reference') ?? $request->query('trxref');

        if (! $reference) {
            return response()->json([
                'status'  => 'error',
                'code'    => 'PAYMENT_CALLBACK_MISSING_REFERENCE',
                'message' => 'Référence de transaction manquante.',
            ], 400);
        }

        // Verify the reference exists to prevent reference enumeration
        $transaction = Transaction::where('idempotency_key', $reference)
            ->orWhere('gateway_reference', $reference)
            ->first();

        if (! $transaction) {
            return response()->json([
                'status'  => 'error',
                'code'    => 'PAYMENT_CALLBACK_UNKNOWN_REFERENCE',
                'message' => 'Référence de transaction inconnue.',
            ], 404);
        }

        return response()->json(['status' => 'received', 'reference' => $reference]);
    }

    /**
     * GET /v1/payments/{transaction}/status
     *
     * Poll the current status of a transaction.
     * The authenticated user must be the client who initiated the payment.
     */
    public function status(Transaction $transaction): JsonResponse
    {
        abort_if(
            $transaction->bookingRequest->client_id !== request()->user()->id,
            403,
            "Vous n'êtes pas autorisé à consulter cette transaction.",
        );

        return response()->json(new TransactionResource($transaction));
    }
}
