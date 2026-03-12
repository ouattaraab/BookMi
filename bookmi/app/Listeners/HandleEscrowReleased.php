<?php

namespace App\Listeners;

use App\Events\EscrowReleased;
use App\Jobs\SendPushNotification;
use App\Notifications\EscrowReleasedAdminClientNotification;
use App\Notifications\EscrowReleasedAdminTalentNotification;
use Illuminate\Support\Facades\Notification;

class HandleEscrowReleased
{
    /**
     * Lorsqu'un escrow est libéré :
     * 1. Crédite le solde disponible du talent (available_balance).
     * 2. Notifie le talent par push FCM (message adapté selon l'origine de la libération).
     * 3. Si libéré par un admin : notifie aussi le client par push + envoie un email aux deux parties.
     */
    public function handle(EscrowReleased $event): void
    {
        $escrowHold = $event->escrowHold;
        $booking    = $escrowHold->bookingRequest;

        if (! $booking) {
            return;
        }

        // Eager-load les relations nécessaires aux notifications
        $booking->loadMissing(['talentProfile.user', 'client']);

        $talent         = $booking->talentProfile;
        $releasedByType = $escrowHold->released_by_type ?? 'system';
        $isAdminRelease = $releasedByType === 'admin';

        if (! $talent) {
            return;
        }

        // 1. Créditer le solde disponible du talent
        $talent->increment('available_balance', $escrowHold->cachet_amount);

        // 2. Push FCM au talent (message contextuel selon l'origine)
        if ($talent->user_id) {
            $amount       = number_format($escrowHold->cachet_amount, 0, ',', ' ');
            $pushBody     = $isAdminRelease
                ? "L'administrateur BookMi a libéré {$amount} XOF. Ils ont été ajoutés à votre solde."
                : "{$amount} XOF ont été ajoutés à votre solde disponible.";

            dispatch(new SendPushNotification(
                userId: $talent->user_id,
                title:  'Revenus disponibles',
                body:   $pushBody,
                data:   ['booking_id' => (string) $booking->id, 'type' => 'balance_credited'],
            ));
        }

        // 3. Si libéré manuellement par un admin : notifier aussi le client
        if ($isAdminRelease) {
            // Push FCM au client
            $client = $booking->client;
            if ($client) {
                dispatch(new SendPushNotification(
                    userId: $client->id,
                    title:  'Fonds libérés',
                    body:   "L'administrateur BookMi a libéré les fonds de votre réservation.",
                    data:   ['booking_id' => (string) $booking->id, 'type' => 'escrow_released_admin'],
                ));

                // Email au client
                Notification::send($client, new EscrowReleasedAdminClientNotification($booking));
            }

            // Email au talent
            if ($talent->user) {
                Notification::send($talent->user, new EscrowReleasedAdminTalentNotification(
                    $booking,
                    (float) $escrowHold->cachet_amount,
                ));
            }
        }
    }
}
