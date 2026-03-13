<?php

namespace App\Jobs;

use App\Models\TalentFollow;
use App\Models\User;
use App\Notifications\TalentNewContentNotification;
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

    /**
     * @param string $contentType 'meet_and_greet' | 'photo' | 'video' | 'link' | 'general'
     * @param string $talentSlug  Slug for building the profile URL in the email
     */
    public function __construct(
        public readonly int $talentProfileId,
        public readonly string $stageName,
        public readonly string $title,
        public readonly string $body,
        public readonly string $contentType = 'general',
        public readonly string $talentSlug = '',
    ) {
    }

    public function handle(): void
    {
        $profileUrl = $this->talentSlug
            ? url('/talents/' . $this->talentSlug)
            : url('/talents');

        TalentFollow::where('talent_profile_id', $this->talentProfileId)
            ->with('user')
            ->get()
            ->each(function (TalentFollow $follow) use ($profileUrl): void {
                /** @var User|null $user */
                $user = $follow->user instanceof User ? $follow->user : null;
                if (! $user) {
                    return;
                }

                // In-app + FCM (via existing job which creates PushNotification record + sends FCM)
                SendPushNotification::dispatch(
                    $user->id,
                    $this->title,
                    $this->body,
                    [
                        'type'              => 'talent_update',
                        'talent_profile_id' => (string) $this->talentProfileId,
                    ],
                );

                // Email (respects user preference follow_update)
                if ($user->getNotificationPreference('follow_update')) {
                    $user->notify(new TalentNewContentNotification(
                        stageName:   $this->stageName,
                        title:       $this->title,
                        body:        $this->body,
                        contentType: $this->contentType,
                        profileUrl:  $profileUrl,
                    ));
                }
            });
    }
}
