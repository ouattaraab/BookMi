<?php

namespace App\Notifications;

use App\Models\WithdrawalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WithdrawalRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly WithdrawalRequest $withdrawalRequest,
    ) {
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $request    = $this->withdrawalRequest;
        $profile    = $request->talentProfile;
        $talentName = $profile?->stage_name
            ?? trim(($profile?->user?->first_name ?? '') . ' ' . ($profile?->user?->last_name ?? ''))
            ?: 'Talent';
        $amount    = number_format($request->amount, 0, ',', ' ');
        $method    = $request->payout_method?->value ?? '—';
        $phone     = data_get($request->payout_details, 'phone', '—');
        $adminUrl  = url('/admin/withdrawal-requests/' . $request->id);

        return (new MailMessage())
            ->subject('Nouvelle demande de reversement — BookMi')
            ->greeting('Bonjour,')
            ->line("**{$talentName}** a effectué une demande de reversement.")
            ->line("**Montant demandé :** {$amount} XOF")
            ->line("**Méthode :** {$method}")
            ->line("**Compte :** {$phone}")
            ->line('Veuillez traiter cette demande dans les meilleurs délais.')
            ->action('Voir la demande', $adminUrl)
            ->salutation('L\'équipe BookMi');
    }
}
