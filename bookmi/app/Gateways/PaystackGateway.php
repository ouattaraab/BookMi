<?php

namespace App\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\Exceptions\PaymentException;
use Illuminate\Support\Facades\Http;

class PaystackGateway implements PaymentGatewayInterface
{
    private string $baseUrl = 'https://api.paystack.co';

    public function name(): string
    {
        return 'paystack';
    }

    public function initializeTransaction(array $payload): array
    {
        $response = $this->http()->post("{$this->baseUrl}/transaction/initialize", $payload);

        $data = $response->json();

        if (! $response->successful() || ! ($data['status'] ?? false)) {
            throw PaymentException::gatewayError('paystack', $data['message'] ?? 'Unknown error');
        }

        return $data['data'];
    }

    public function initiateCharge(array $payload): array
    {
        $response = $this->http()->post("{$this->baseUrl}/charge", $payload);

        $data = $response->json();

        if (! $response->successful() || ! ($data['status'] ?? false)) {
            throw PaymentException::gatewayError('paystack', $data['message'] ?? 'Unknown error');
        }

        return $data['data'];
    }

    public function verifyTransaction(string $reference): array
    {
        $response = $this->http()->get("{$this->baseUrl}/transaction/verify/{$reference}");

        $data = $response->json();

        if (! $response->successful() || ! ($data['status'] ?? false)) {
            throw PaymentException::gatewayError('paystack', $data['message'] ?? 'Unknown error');
        }

        return $data['data'];
    }

    public function submitOtp(string $reference, string $otp): array
    {
        $response = $this->http()->post("{$this->baseUrl}/charge/submit_otp", [
            'reference' => $reference,
            'otp'       => $otp,
        ]);

        $data = $response->json();

        if (! $response->successful() || ! ($data['status'] ?? false)) {
            throw PaymentException::gatewayError('paystack', $data['message'] ?? 'Unknown error');
        }

        return $data['data'];
    }

    public function initiateTransfer(array $payload): array
    {
        $response = $this->http()->post("{$this->baseUrl}/transfer", $payload);

        $data = $response->json();

        if (! $response->successful() || ! ($data['status'] ?? false)) {
            throw PaymentException::gatewayError('paystack', $data['message'] ?? 'Unknown error');
        }

        return $data['data'];
    }

    /**
     * Preconfigured HTTP client — timeout enforced per NFR4 (max 15s external calls).
     */
    private function http(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withToken($this->secretKey())->timeout(15);
    }

    private function secretKey(): string
    {
        return (string) (config('services.paystack.secret_key') ?? '');
    }
}
