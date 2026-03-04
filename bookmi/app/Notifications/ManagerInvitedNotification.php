<?php

namespace App\Notifications;

use App\Models\ManagerInvitation;
use App\Models\TalentProfile;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ManagerInvitedNotification extends Notification
{
    public function __construct(
        private readonly TalentProfile $profile,
        private readonly ManagerInvitation $invitation,
    ) {
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $profileUser = $this->profile->user;
        $talentName  = $this->profile->stage_name
            ?? trim(($profileUser->first_name ?? '') . ' ' . ($profileUser->last_name ?? ''))
            ?: 'Un talent';

        $respondUrl = route('manager.invitations.respond', ['token' => $this->invitation->token]);

        return (new MailMessage())
            ->subject("Invitation manager BookMi — {$talentName}")
            ->markdown('emails.manager-invited', [
                'talentName' => $talentName,
                'respondUrl' => $respondUrl,
            ]);
    }
}
