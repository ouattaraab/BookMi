<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
     * Security:
     *  - [C1] Production rejects if PAYSTACK_WEBHOOK_SECRET is missing.
     *  - [C2] Nonce-based replay protection: duplicate event IDs are rejected.
     *
     * NFR42: webhook signature validation.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.paystack.webhook_secret');

        if (empty($secret)) {
            if (app()->isProduction()) {
                Log::critical('Paystack webhook rejected: PAYSTACK_WEBHOOK_SECRET is not set in production.');

                return response()->json([
                    'error' => [
                        'code'    => 'WEBHOOK_CONFIG_ERROR',
                        'message' => 'Webhook non configuré.',
                        'status'  => 500,
                        'details' => new \stdClass(),
                    ],
                ], 500);
            }

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

        // [C2] Replay protection: reject duplicate event IDs (24 h window).
        $body = json_decode($request->getContent(), true);
        $eventId = $body['data']['id'] ?? null;

        if ($eventId !== null) {
            $nonceKey = 'paystack_webhook_nonce:' . $eventId;

            if (Cache::has($nonceKey)) {
                // Already processed — return 200 so Paystack stops retrying.
                return response()->json(['message' => 'Webhook déjà traité.'], 200);
            }

            Cache::put($nonceKey, true, now()->addHours(24));
        }

        return $next($request);
    }
}
