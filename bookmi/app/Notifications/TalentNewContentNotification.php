<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TalentNewContentNotification extends Notification
{
    use Queueable;

    /**
     * @param string $contentType 'meet_and_greet' | 'photo' | 'video' | 'link' | 'general'
     */
    public function __construct(
        public readonly string $stageName,
        public readonly string $title,
        public readonly string $body,
        public readonly string $contentType = 'general',
        public readonly string $profileUrl = '',
    ) {
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $contentLabel = match ($this->contentType) {
            'meet_and_greet' => 'un Meet & Greet',
            'photo'          => 'une nouvelle photo',
            'video'          => 'une nouvelle vidéo',
            'link'           => 'un nouveau contenu',
            default          => 'du nouveau contenu',
        };

        /** @var \App\Models\User $notifiable */
        $clientName = trim($notifiable->first_name . ' ' . $notifiable->last_name) ?: 'Abonné(e)';

        return (new MailMessage())
            ->subject($this->title)
            ->view('emails.talent-new-content', [
                'clientName'   => $clientName,
                'stageName'    => $this->stageName,
                'contentLabel' => $contentLabel,
                'title'        => $this->title,
                'body'         => $this->body,
                'profileUrl'   => $this->profileUrl,
            ]);
    }
}
