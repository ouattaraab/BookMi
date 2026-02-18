<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ValidatePaystackSignature
{
    /**
     * Validate the HMAC-SHA512 signature sent by Paystack on every webhook.
     *
     * Paystack signs the raw request body with the webhook secret key and sends
     * the result in the `x-paystack-signature` header (hex string).
     *
     * NFR42: webhook signature validation.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.paystack.webhook_secret');

        // Skip validation when no secret is configured (local/CI without real key).
        // Log a warning so operators detect a missing secret in production.
        if (empty($secret)) {
            Log::warning('Paystack webhook signature validation bypassed: PAYSTACK_WEBHOOK_SECRET is not configured.');

            return $next($request);
        }

        $receivedSignature = $request->header('x-paystack-signature', '');
        $computedSignature = hash_hmac('sha512', $request->getContent(), $secret);

        if (! hash_equals($computedSignature, $receivedSignature)) {
            return response()->json([
                'error' => [
                    'code'    => 'WEBHOOK_SIGNATURE_INVALID',
                    'message' => 'Signature de webhook invalide.',
                    'status'  => 401,
                    'details' => new \stdClass(),
                ],
            ], 401);
        }

        return $next($request);
    }
}
