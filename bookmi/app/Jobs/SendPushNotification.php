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

        // Check user notification preferences
        $notifType = $this->data['type'] ?? null;
        $prefKey = match (true) {
            $notifType === 'new_message'                           => 'new_message',
            str_starts_with((string) $notifType, 'booking_')
                || in_array($notifType, ['dispute_opened', 'reminder_7d', 'reminder_2d',
                    'reschedule_proposed', 'reschedule_accepted', 'reschedule_rejected',
                    'client_confirmed', 'talent_confirmed', 'booking_completed']) => 'booking_updates',
            $notifType === 'new_review'                            => 'new_review',
            $notifType === 'follow_update'                         => 'follow_update',
            $notifType === 'admin_broadcast'                       => 'admin_broadcast',
            default                                                => null,
        };

        if ($prefKey !== null && ! $user->getNotificationPreference($prefKey)) {
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
