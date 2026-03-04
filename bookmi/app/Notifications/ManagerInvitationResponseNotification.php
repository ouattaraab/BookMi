<?php

namespace App\Notifications;

use App\Enums\ManagerInvitationStatus;
use App\Models\ManagerInvitation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ManagerInvitationResponseNotification extends Notification
{
    public function __construct(
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
        $invitation  = $this->invitation;
        $managerRelation = $invitation->manager;
        $managerName = $managerRelation !== null
            ? $managerRelation->first_name
            : explode('@', $invitation->manager_email)[0];

        $accepted = $invitation->status === ManagerInvitationStatus::Accepted;
        $statusLabel = $accepted ? 'accepté' : 'refusé';

        $talentProfile     = $invitation->talentProfile;
        $talentProfileUser = $talentProfile->user;
        $talentName        = $talentProfile->stage_name
            ?? trim(
                (($talentProfileUser ? $talentProfileUser->first_name : '') ?? '') . ' ' .
                (($talentProfileUser ? $talentProfileUser->last_name : '') ?? ''),
            )
            ?: 'Votre talent';

        return (new MailMessage())
            ->subject("Réponse invitation manager — {$managerName} a {$statusLabel} votre invitation")
            ->markdown('emails.manager-invitation-response', [
                'managerName' => $managerName,
                'talentName'  => $talentName,
                'accepted'    => $accepted,
                'statusLabel' => $statusLabel,
                'comment'     => $invitation->manager_comment,
            ]);
    }
}
