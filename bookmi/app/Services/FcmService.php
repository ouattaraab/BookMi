<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends push notifications via Firebase Cloud Messaging (HTTP v1 API).
 *
 * Configuration keys (config/services.php or env):
 *   FCM_PROJECT_ID    — Firebase project ID
 *   FCM_SERVER_KEY    — Service account access token (or OAuth bearer token)
 *
 * In test environments, set FCM_SERVER_KEY to empty to skip actual HTTP calls.
 */
class FcmService
{
    private string $projectId;
    private string $serverKey;

    public function __construct()
    {
        $this->projectId = (string) config('services.fcm.project_id', '');
        $this->serverKey = (string) config('services.fcm.server_key', '');
    }

    /**
     * Sends a push notification to a single FCM device token.
     *
     * Returns true on success, false on failure or if FCM is not configured.
     *
     * @param array<string, mixed> $data
     */
    public function send(string $deviceToken, string $title, string $body, array $data = []): bool
    {
        if (empty($this->serverKey) || empty($this->projectId)) {
            Log::info('FCM not configured — push notification skipped.', compact('title', 'body'));
            return false;
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        $payload = [
            'message' => [
                'token'        => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                ],
                'data'         => array_map('strval', $data),
            ],
        ];

        try {
            $response = Http::withToken($this->serverKey)
                ->acceptJson()
                ->post($url, $payload);

            if ($response->successful()) {
                return true;
            }

            Log::warning('FCM push notification failed.', [
                'status'   => $response->status(),
                'response' => $response->json(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('FCM HTTP exception.', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
