<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $role // 'client' | 'talent'
    ) {}

    /**
     * @param User $notifiable
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * @param User $notifiable
     */
    public function toMail(object $notifiable): MailMessage
    {
        $view = $this->role === 'talent'
            ? 'emails.welcome-talent'
            : 'emails.welcome-client';

        $subject = 'Akwaba sur BookMi, ' . $notifiable->first_name . ' ! 🎉';

        return (new MailMessage)
            ->subject($subject)
            ->view($view, ['user' => $notifiable]);
    }
}
