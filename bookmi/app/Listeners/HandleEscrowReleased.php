<?php

namespace App\Listeners;

use App\Events\EscrowReleased;
use App\Jobs\SendPushNotification;

class HandleEscrowReleased
{
    /**
     * Lorsqu'un escrow est libéré :
     * 1. Crédite le solde disponible du talent (available_balance).
     * 2. Notifie le talent par push.
     */
    public function handle(EscrowReleased $event): void
    {
        $escrowHold = $event->escrowHold;
        $booking    = $escrowHold->bookingRequest;

        if (! $booking) {
            return;
        }

        $talent = $booking->talentProfile;

        if (! $talent) {
            return;
        }

        // Créditer le solde disponible du talent
        $talent->increment('available_balance', $escrowHold->cachet_amount);

        // Notifier le talent
        if ($talent->user_id) {
            $amount = number_format($escrowHold->cachet_amount, 0, ',', ' ');
            dispatch(new SendPushNotification(
                userId: $talent->user_id,
                title:  'Revenus disponibles',
                body:   "{$amount} XOF ont été ajoutés à votre solde disponible.",
                data:   ['booking_id' => (string) $booking->id, 'type' => 'balance_credited'],
            ));
        }
    }
}
