<?php

namespace App\Notifications;

use App\Models\WithdrawalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifie le talent quand sa demande de reversement est approuvée, rejetée ou complétée.
 */
class WithdrawalStatusNotification extends Notification
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
        $request = $this->withdrawalRequest;
        $amount  = number_format($request->amount, 0, ',', ' ');
        $status  = $request->status;

        $subject = match($status->value) {
            'approved'   => "Demande de reversement approuvée — {$amount} XOF",
            'completed'  => "Reversement effectué — {$amount} XOF",
            'rejected'   => "Demande de reversement refusée — BookMi",
            default      => "Mise à jour de votre demande de reversement — BookMi",
        };

        $message = (new MailMessage())->subject($subject)->greeting('Bonjour,');

        return match($status->value) {
            'approved' => $message
                ->line("Votre demande de reversement de **{$amount} XOF** a été **approuvée**.")
                ->line('Le virement vers votre compte sera effectué prochainement.')
                ->line('Le montant reçu sera net des frais de transfert de la plateforme.')
                ->salutation('L\'équipe BookMi'),

            'completed' => $message
                ->line("Votre reversement de **{$amount} XOF** a été **effectué**.")
                ->line('Vérifiez votre compte de réception dans les prochaines minutes.')
                ->salutation('L\'équipe BookMi'),

            'rejected' => $message
                ->line("Votre demande de reversement de **{$amount} XOF** a été **refusée**.")
                ->when(
                    $request->note,
                    fn ($mail) => $mail->line("**Motif :** {$request->note}"),
                )
                ->line('Le montant a été recrédité sur votre solde disponible.')
                ->line('Pour toute question, contactez le support BookMi.')
                ->salutation('L\'équipe BookMi'),

            default => $message
                ->line("Votre demande de reversement de **{$amount} XOF** est **en cours de traitement**.")
                ->salutation('L\'équipe BookMi'),
        };
    }
}
