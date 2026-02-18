<?php

namespace App\Http\Controllers\Api\V1;

use App\Jobs\HandlePaymentWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaystackWebhookController extends BaseController
{
    /**
     * POST /v1/webhooks/paystack
     *
     * Receive Paystack webhook events. Signature is already validated by
     * ValidatePaystackSignature middleware. We immediately return 200 so
     * Paystack doesn't retry unnecessarily, then dispatch the job.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->json()->all();
        $event   = $payload['event'] ?? '';
        $data    = $payload['data']  ?? [];

        if ($event) {
            HandlePaymentWebhook::dispatch($event, $data);
        }

        return response()->json(['status' => 'received']);
    }
}
