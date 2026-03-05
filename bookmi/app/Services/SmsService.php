<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private const TOKEN_URL      = 'https://api.orange.com/oauth/v3/token';
    private const MESSAGING_BASE = 'https://api.orange.com/smsmessaging/v1';
    private const TOKEN_CACHE_KEY = 'orange_sms_access_token';

    public function send(string $phone, string $message): bool
    {
        if (! config('bookmi.sms.enabled', false)) {
            Log::info('[SMS disabled] Would send to ' . $phone . ': ' . $message);

            return false;
        }

        try {
            $this->doSend($phone, $message);
            Log::info('[SMS] Sent to ' . $phone);

            return true;
        } catch (\Throwable $e) {
            Log::error('[SMS] Failed to send to ' . $phone . ': ' . $e->getMessage());

            return false;
        }
    }

    public function sendOtp(string $phone, string $code): void
    {
        $this->send($phone, "Votre code BookMi : {$code}. Valable 10 minutes.");
    }

    /**
     * Performs the actual HTTP call to the Orange SMS API.
     * Retries once on 401 (expired token) after refreshing the cached token.
     */
    private function doSend(string $phone, string $message, bool $retried = false): void
    {
        $token        = $this->getAccessToken();
        $senderNumber = ltrim((string) config('bookmi.sms.sender_number'), '+');
        $senderName   = (string) config('bookmi.sms.sender_name', 'BookMi');

        $body = [
            'outboundSMSMessageRequest' => [
                'address'                => 'tel:+' . ltrim($phone, '+'),
                'senderAddress'          => 'tel:+' . $senderNumber,
                'senderName'             => $senderName,
                'outboundSMSTextMessage' => ['message' => $message],
            ],
        ];

        $url = self::MESSAGING_BASE . '/outbound/tel%3A%2B' . $senderNumber . '/requests';

        $response = Http::withToken($token)
            ->asJson()
            ->acceptJson()
            ->post($url, $body);

        if ($response->status() === 401 && ! $retried) {
            Cache::forget(self::TOKEN_CACHE_KEY);
            $this->doSend($phone, $message, retried: true);

            return;
        }

        if (! $response->successful()) {
            throw new \RuntimeException(
                'Orange SMS API returned ' . $response->status() . ': ' . $response->body()
            );
        }
    }

    /**
     * Returns a valid OAuth 2.0 access token, cached for 3 500 s (token TTL is 3 600 s).
     */
    private function getAccessToken(): string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, 3500, function (): string {
            $clientId     = (string) config('bookmi.sms.client_id');
            $clientSecret = (string) config('bookmi.sms.client_secret');

            $response = Http::withBasicAuth($clientId, $clientSecret)
                ->asForm()
                ->acceptJson()
                ->post(self::TOKEN_URL, ['grant_type' => 'client_credentials']);

            if (! $response->successful()) {
                throw new \RuntimeException(
                    'Orange SMS token request failed: ' . $response->body()
                );
            }

            return (string) $response->json('access_token');
        });
    }
}
