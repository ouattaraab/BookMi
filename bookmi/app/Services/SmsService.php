<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(string $phone, string $message): bool
    {
        if (! config('bookmi.sms.enabled', false)) {
            Log::info('[SMS disabled] Would send to ' . $phone . ': ' . $message);

            return false;
        }

        try {
            $at  = new \AfricasTalking\SDK\AfricasTalking(
                config('bookmi.sms.username'),
                config('bookmi.sms.api_key'),
            );
            $sms    = $at->sms();
            $result = $sms->send([
                'to'      => $phone,
                'message' => $message,
                'from'    => config('bookmi.sms.sender_id', 'BookMi'),
            ]);
            Log::info('[SMS] Sent to ' . $phone . ': ' . json_encode($result));

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
}
