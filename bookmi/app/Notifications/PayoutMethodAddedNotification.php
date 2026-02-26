<?php

namespace App\Notifications;

use App\Models\TalentProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayoutMethodAddedNotification extends Notification
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
        $profile   = $this->talentProfile;
        $talentName = $profile->stage_name
            ?? trim(($profile->user?->first_name ?? '') . ' ' . ($profile->user?->last_name ?? ''))
            ?: 'Talent';
        $method   = $profile->payout_method ?? '—';
        $phone    = data_get($profile->payout_details, 'phone', '—');
        $adminUrl = url('/admin/talent-profiles/' . $profile->id);

        return (new MailMessage())
            ->subject('Nouveau compte de paiement à valider — BookMi')
            ->greeting('Bonjour,')
            ->line("**{$talentName}** vient de renseigner ses coordonnées bancaires.")
            ->line("**Méthode :** {$method}")
            ->line("**Détails :** {$phone}")
            ->line('Veuillez valider ce compte avant que le talent puisse effectuer des demandes de reversement.')
            ->action('Voir le profil et valider', $adminUrl)
            ->line('Merci de traiter cette demande rapidement.')
            ->salutation('L\'équipe BookMi');
    }
}
