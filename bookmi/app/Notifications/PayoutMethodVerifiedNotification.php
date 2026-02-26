<?php

namespace App\Notifications;

use App\Models\TalentProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayoutMethodVerifiedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly TalentProfile $talentProfile,
    ) {
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $profile = $this->talentProfile;
        $method = $profile->payout_method ?? '—';
        $details = data_get(
            $profile->payout_details,
            'phone',
            data_get($profile->payout_details, 'account_number', '—')
        );
        $portalUrl = url('/talent-portal/withdrawal-request');

        return (new MailMessage())
            ->subject('Votre compte de paiement a été validé — BookMi')
            ->greeting('Bonne nouvelle !')
            ->line("Votre compte **{$method}** ({$details}) a été **validé** par l'administration BookMi.")
            ->line('Vous pouvez désormais effectuer des demandes de reversement depuis votre espace talent.')
            ->action('Faire une demande de reversement', $portalUrl)
            ->salutation('L\'équipe BookMi');
    }
}
