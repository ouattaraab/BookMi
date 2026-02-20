<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorCodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $code,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Votre code de vérification BookMi')
            ->greeting('Bonjour !')
            ->line('Votre code de vérification à deux facteurs est :')
            ->line("**{$this->code}**")
            ->line('Ce code est valable pendant **10 minutes**.')
            ->line('Si vous n\'avez pas demandé ce code, ignorez ce message.')
            ->salutation('L\'équipe BookMi');
    }
}
