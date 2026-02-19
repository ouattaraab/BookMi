<?php

namespace App\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\Exceptions\PaymentException;
use Illuminate\Support\Facades\Http;

class CinetPayGateway implements PaymentGatewayInterface
{
    private string $baseUrl = 'https://api-checkout.cinetpay.com/v2';

    public function name(): string
    {
        return 'cinetpay';
    }

    /**
     * Initiate a mobile money charge via CinetPay payment link.
     *
     * CinetPay uses a unified payment endpoint — channels are passed as 'MOBILE_MONEY'.
     */
    public function initiateCharge(array $payload): array
    {
        $response = $this->http()->post("{$this->baseUrl}/payment", array_merge([
            'apikey'  => $this->apiKey(),
            'site_id' => $this->siteId(),
            'channels' => 'MOBILE_MONEY',
        ], $this->mapPayload($payload)));

        $data = $response->json();

        if (! $response->successful() || ($data['code'] ?? '') !== '201') {
            throw PaymentException::gatewayError('cinetpay', $data['message'] ?? 'Unknown error');
        }

        return [
            'reference'         => $payload['reference'] ?? null,
            'payment_url'       => $data['data']['payment_url'] ?? null,
            'authorization_url' => $data['data']['payment_url'] ?? null,
        ];
    }

    /**
     * Initialize a card payment transaction via CinetPay.
     *
     * Returns a payment_url (redirect URL) as authorization_url.
     */
    public function initializeTransaction(array $payload): array
    {
        $response = $this->http()->post("{$this->baseUrl}/payment", array_merge([
            'apikey'   => $this->apiKey(),
            'site_id'  => $this->siteId(),
            'channels' => 'ALL',
        ], $this->mapPayload($payload)));

        $data = $response->json();

        if (! $response->successful() || ($data['code'] ?? '') !== '201') {
            throw PaymentException::gatewayError('cinetpay', $data['message'] ?? 'Unknown error');
        }

        return [
            'reference'         => $payload['reference'] ?? null,
            'authorization_url' => $data['data']['payment_url'] ?? null,
            'access_code'       => null, // CinetPay does not have access_code
        ];
    }

    /**
     * Verify a transaction by its reference.
     *
     * CinetPay uses POST /payment/check.
     */
    public function verifyTransaction(string $reference): array
    {
        $response = $this->http()->post("{$this->baseUrl}/payment/check", [
            'apikey'         => $this->apiKey(),
            'site_id'        => $this->siteId(),
            'transaction_id' => $reference,
        ]);

        $data = $response->json();

        if (! $response->successful() || ($data['code'] ?? '') !== '00') {
            throw PaymentException::gatewayError('cinetpay', $data['message'] ?? 'Unknown error');
        }

        return $data['data'] ?? [];
    }

    /**
     * CinetPay does not support OTP submission.
     */
    public function submitOtp(string $reference, string $otp): array
    {
        throw PaymentException::unsupportedMethod('cinetpay:submit_otp');
    }

    /**
     * CinetPay does not support transfer recipient creation.
     */
    public function createTransferRecipient(array $payload): array
    {
        throw PaymentException::unsupportedMethod('cinetpay:create_transfer_recipient');
    }

    /**
     * CinetPay does not support direct talent payouts.
     */
    public function initiateTransfer(array $payload): array
    {
        throw PaymentException::unsupportedMethod('cinetpay:initiate_transfer');
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    /**
     * Map from our internal payload format to CinetPay's format.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function mapPayload(array $payload): array
    {
        return [
            'transaction_id'        => $payload['reference'] ?? null,
            'amount'                => $payload['amount'] ?? 0,
            'currency'              => $payload['currency'] ?? 'XOF',
            'description'           => $payload['description'] ?? 'BookMi payment',
            'customer_phone_number' => $payload['mobile_money']['phone'] ?? null,
            'return_url'            => $payload['callback_url'] ?? null,
        ];
    }

    private function http(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::timeout(15)->acceptJson();
    }

    private function apiKey(): string
    {
        return (string) (config('services.cinetpay.api_key') ?? '');
    }

    private function siteId(): string
    {
        return (string) (config('services.cinetpay.site_id') ?? '');
    }
}
