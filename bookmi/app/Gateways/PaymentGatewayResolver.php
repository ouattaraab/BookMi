<?php

namespace App\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\Exceptions\PaymentException;
use Illuminate\Support\Facades\Log;

/**
 * Decorator that transparently wraps primary + fallback gateway.
 *
 * On PaymentException::gatewayError from the primary gateway, it:
 * 1. Logs a warning with context.
 * 2. Retries the same operation on the fallback gateway.
 *
 * Methods that are unsupported by the fallback (submitOtp, createTransferRecipient,
 * initiateTransfer) are NOT retried on the fallback — the primary exception is re-thrown.
 */
class PaymentGatewayResolver implements PaymentGatewayInterface
{
    /** Methods that should NOT fall back to the secondary gateway. */
    private const NO_FALLBACK_METHODS = [
        'submitOtp',
        'createTransferRecipient',
        'initiateTransfer',
    ];

    public function __construct(
        private readonly PaymentGatewayInterface $primary,
        private readonly PaymentGatewayInterface $fallback,
    ) {}

    public function name(): string
    {
        return $this->primary->name();
    }

    public function initiateCharge(array $payload): array
    {
        return $this->withFallback('initiateCharge', fn (PaymentGatewayInterface $gw) => $gw->initiateCharge($payload));
    }

    public function initializeTransaction(array $payload): array
    {
        return $this->withFallback('initializeTransaction', fn (PaymentGatewayInterface $gw) => $gw->initializeTransaction($payload));
    }

    public function verifyTransaction(string $reference): array
    {
        return $this->withFallback('verifyTransaction', fn (PaymentGatewayInterface $gw) => $gw->verifyTransaction($reference));
    }

    public function submitOtp(string $reference, string $otp): array
    {
        // OTP submission is Paystack-specific — no CinetPay fallback
        return $this->primary->submitOtp($reference, $otp);
    }

    public function createTransferRecipient(array $payload): array
    {
        // Transfer recipients are Paystack-specific — no CinetPay fallback
        return $this->primary->createTransferRecipient($payload);
    }

    public function initiateTransfer(array $payload): array
    {
        // Payouts are Paystack-specific — no CinetPay fallback
        return $this->primary->initiateTransfer($payload);
    }

    /**
     * Execute $operation on primary gateway; on gatewayError, retry with fallback.
     *
     * @param  callable(PaymentGatewayInterface): array<string, mixed>  $operation
     * @return array<string, mixed>
     */
    private function withFallback(string $method, callable $operation): array
    {
        try {
            return $operation($this->primary);
        } catch (PaymentException $e) {
            if ($e->getErrorCode() !== 'PAYMENT_GATEWAY_ERROR') {
                throw $e; // Non-gateway errors (validation, duplicate, etc.) are not retried
            }

            Log::warning("Primary gateway [{$this->primary->name()}] failed for {$method}. Switching to fallback [{$this->fallback->name()}].", [
                'error' => $e->getMessage(),
            ]);

            return $operation($this->fallback);
        }
    }
}
