<?php

namespace App\Services;

use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;

class FcmService
{
    public function __construct(private Messaging $messaging)
    {
    }

    /**
     * Sends a push notification to a single FCM device token.
     * Returns true on success, false on failure or if token is empty.
     *
     * @param array<string, mixed> $data
     */
    public function send(string $deviceToken, string $title, string $body, array $data = []): bool
    {
        if (empty($deviceToken)) {
            return false;
        }

        try {
            $message = CloudMessage::withTarget('token', $deviceToken)
                ->withNotification(Notification::create($title, $body))
                ->withData(array_map('strval', $data));

            $this->messaging->send($message);
            return true;
        } catch (\Throwable $e) {
            Log::warning('FCM push notification failed.', [
                'token'  => substr($deviceToken, 0, 20) . '...',
                'title'  => $title,
                'error'  => $e->getMessage(),
            ]);
            return false;
        }
    }
}
