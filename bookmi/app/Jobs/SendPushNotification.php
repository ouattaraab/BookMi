<?php

namespace App\Jobs;

use App\Models\PushNotification;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPushNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Number of retry attempts before the job is considered failed.
     */
    public int $tries = 3;

    /**
     * Delay (seconds) between retries.
     */
    public int $backoff = 10;

    /**
     * @param array<string, mixed> $data Extra payload attached to the notification.
     */
    public function __construct(
        public readonly int $userId,
        public readonly string $title,
        public readonly string $body,
        public readonly array $data = [],
    ) {
    }

    public function handle(FcmService $fcm): void
    {
        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        // Persist notification record first
        $notification = PushNotification::create([
            'user_id' => $this->userId,
            'title'   => $this->title,
            'body'    => $this->body,
            'data'    => $this->data,
        ]);

        // Attempt FCM delivery if the user has a device token
        if ($user->fcm_token) {
            $sent = $fcm->send($user->fcm_token, $this->title, $this->body, $this->data);

            if ($sent) {
                $notification->update(['sent_at' => now()]);
            }
        }
    }
}
