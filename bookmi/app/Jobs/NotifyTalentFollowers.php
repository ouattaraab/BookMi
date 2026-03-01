<?php

namespace App\Jobs;

use App\Models\TalentFollow;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyTalentFollowers implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $talentProfileId,
        public readonly string $stageName,
        public readonly string $title,
        public readonly string $body,
    ) {
    }

    public function handle(): void
    {
        TalentFollow::where('talent_profile_id', $this->talentProfileId)
            ->pluck('user_id')
            ->each(function (int $userId): void {
                SendPushNotification::dispatch(
                    $userId,
                    $this->title,
                    $this->body,
                    [
                        'type'              => 'talent_update',
                        'talent_profile_id' => (string) $this->talentProfileId,
                    ],
                );
            });
    }
}
