<?php

namespace Tests\Fakes;

use App\Services\SmsService;

/**
 * No-op SmsService for use in tests.
 * SMS messages are never actually sent in the test environment.
 */
class FakeSmsService extends SmsService
{
    public function send(string $phone, string $message): bool
    {
        return true; // no-op — SMS are never sent in tests
    }

    public function sendOtp(string $phone, string $code): void
    {
        // no-op
    }
}
