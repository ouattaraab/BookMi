<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayoutMethodRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $reason,
    ) {
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $portalUrl = url('/talent-portal/payout-method');

        return (new MailMessage())
            ->subject('Votre compte de paiement a été refusé — BookMi')
            ->greeting('Bonjour,')
            ->line("Votre compte de paiement n'a pas pu être validé par l'administration BookMi.")
            ->line("**Motif :** {$this->reason}")
            ->line('Veuillez renseigner un nouveau compte de paiement valide depuis votre espace talent.')
            ->action('Mettre à jour mon compte de paiement', $portalUrl)
            ->line('Si vous avez des questions, n\'hésitez pas à nous contacter.')
            ->salutation('L\'équipe BookMi');
    }
}
