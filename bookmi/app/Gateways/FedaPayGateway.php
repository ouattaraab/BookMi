<?php

namespace App\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\Exceptions\PaymentException;
use Illuminate\Support\Facades\Http;

class FedaPayGateway implements PaymentGatewayInterface
{
    private string $baseUrl = 'https://api.fedapay.com/v1';

    public function name(): string
    {
        return 'fedapay';
    }

    /**
     * Initiate a mobile money charge via FedaPay.
     *
     * Flow: POST /transactions → POST /transactions/{id}/pay { mobile_money }
     */
    public function initiateCharge(array $payload): array
    {
        $transaction = $this->createTransaction($payload);
        $txId        = $transaction['id'];

        $response = $this->http()->post("{$this->baseUrl}/transactions/{$txId}/pay", [
            'mobile_money' => [
                'number'   => $payload['mobile_money']['phone'] ?? '',
                'provider' => $payload['mobile_money']['provider'] ?? 'orange',
            ],
        ]);

        $data = $response->json();

        if (! $response->successful() || ! isset($data['v_transaction'])) {
            throw PaymentException::gatewayError('fedapay', $data['message'] ?? $data['error'] ?? 'Unknown error');
        }

        return [
            'reference'    => $payload['reference'] ?? null,
            'status'       => $data['v_transaction']['status'] ?? 'pending',
            'display_text' => $data['v_transaction']['description'] ?? '',
        ];
    }

    /**
     * Initialize a card payment transaction via FedaPay.
     *
     * Flow: POST /transactions → GET /transactions/{id}/token → authorization_url
     */
    public function initializeTransaction(array $payload): array
    {
        $transaction = $this->createTransaction($payload);
        $txId        = $transaction['id'];

        $tokenResponse = $this->http()->get("{$this->baseUrl}/transactions/{$txId}/token");
        $tokenData     = $tokenResponse->json();

        if (! $tokenResponse->successful() || ! isset($tokenData['token'])) {
            throw PaymentException::gatewayError('fedapay', $tokenData['message'] ?? 'Token generation failed');
        }

        $token            = $tokenData['token'];
        $authorizationUrl = "https://pay.fedapay.com/?token={$token}";

        return [
            'reference'         => $payload['reference'] ?? null,
            'authorization_url' => $authorizationUrl,
            'access_code'       => $token,
        ];
    }

    /**
     * Verify a transaction by its reference (stored as FedaPay transaction ID).
     */
    public function verifyTransaction(string $reference): array
    {
        $response = $this->http()->get("{$this->baseUrl}/transactions/{$reference}");
        $data     = $response->json();

        if (! $response->successful() || ! isset($data['v_transaction'])) {
            throw PaymentException::gatewayError('fedapay', $data['message'] ?? 'Unknown error');
        }

        return $data['v_transaction'];
    }

    /**
     * FedaPay does not support OTP submission via the same flow as Paystack.
     */
    public function submitOtp(string $reference, string $otp): array
    {
        throw PaymentException::unsupportedMethod('fedapay:submit_otp');
    }

    /**
     * FedaPay does not support Paystack-style transfer recipients.
     */
    public function createTransferRecipient(array $payload): array
    {
        throw PaymentException::unsupportedMethod('fedapay:create_transfer_recipient');
    }

    /**
     * Payouts remain on Paystack — FedaPay is used only as charge fallback.
     */
    public function initiateTransfer(array $payload): array
    {
        throw PaymentException::unsupportedMethod('fedapay:initiate_transfer');
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    /**
     * Create a FedaPay transaction and return the transaction data.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     * @throws PaymentException
     */
    private function createTransaction(array $payload): array
    {
        $response = $this->http()->post("{$this->baseUrl}/transactions", [
            'description' => 'BookMi payment',
            'amount'      => $payload['amount'] ?? 0,
            'currency'    => ['iso' => $payload['currency'] ?? 'XOF'],
            'customer'    => ['email' => $payload['email'] ?? ''],
            'reference'   => $payload['reference'] ?? null,
        ]);

        $data = $response->json();

        if (! $response->successful() || ! isset($data['v_transaction'])) {
            throw PaymentException::gatewayError('fedapay', $data['message'] ?? $data['error'] ?? 'Transaction creation failed');
        }

        return $data['v_transaction'];
    }

    private function http(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withToken($this->apiKey())->timeout(15)->acceptJson();
    }

    private function apiKey(): string
    {
        return (string) (config('services.fedapay.secret_key') ?? '');
    }
}
