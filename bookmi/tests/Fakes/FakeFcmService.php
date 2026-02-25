<?php

namespace Tests\Fakes;

use App\Services\FcmService;

/**
 * No-op FcmService for use in tests.
 *
 * Extends FcmService so it satisfies the `FcmService $fcm` type-hint in
 * SendPushNotification::handle() (method injection).  The parent constructor
 * is intentionally skipped to avoid the Firebase credentials requirement.
 */
class FakeFcmService extends FcmService
{
    public function __construct()
    {
        // Bypass parent::__construct(Kreait\Firebase\Contract\Messaging $messaging)
    }

    public function send(string $deviceToken, string $title, string $body, array $data = []): bool
    {
        return true; // no-op — push notifications are never sent in tests
    }
}
