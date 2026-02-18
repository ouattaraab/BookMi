<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SmsService
{
    public function sendOtp(string $phone, string $code): void
    {
        Log::info("OTP sent to {$phone}");
    }
}
