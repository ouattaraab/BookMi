<?php

namespace App\Notifications;

use App\Models\TalentNotificationRequest;
use App\Models\TalentProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TalentAvailableNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly TalentProfile $talent,
        private readonly TalentNotificationRequest $request,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $talentName = $this->talent->stage_name;
        $talentUrl  = url('/talents/' . ($this->talent->slug ?? $this->talent->id));

        return (new MailMessage())
            ->subject($talentName . ' est maintenant disponible sur BookMi !')
            ->markdown('emails.talent-available', [
                'talentName'  => $talentName,
                'talentUrl'   => $talentUrl,
                'searchQuery' => $this->request->search_query,
                'category'    => $this->talent->category?->name,
                'city'        => $this->talent->city,
            ]);
    }
}
