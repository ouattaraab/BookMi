<?php

namespace App\Listeners;

use App\Events\EscrowReleased;
use App\Jobs\ProcessPayout;
use App\Jobs\SendPushNotification;

class HandleEscrowReleased
{
    /**
     * Dispatch the ProcessPayout job with a configurable delay (default 24h per NFR).
     * The delay allows BookMi to review the payout before it hits the talent's account.
     */
    public function handle(EscrowReleased $event): void
    {
        $delayHours = config('bookmi.escrow.payout_delay_hours', 24);

        ProcessPayout::dispatch($event->escrowHold->id)
            ->delay(now()->addHours($delayHours));

        // Notify talent that payout is being processed
        $escrowHold = $event->escrowHold;
        $booking    = $escrowHold->bookingRequest;
        if ($booking) {
            $talent = $booking->talentProfile;
            if ($talent && $talent->user_id) {
                $amount = number_format($escrowHold->cachet_amount / 100, 0, ',', ' ');
                dispatch(new SendPushNotification(
                    userId: $talent->user_id,
                    title:  'Virement en cours',
                    body:   "Votre virement de {$amount} XOF a été initié.",
                    data:   ['booking_id' => (string) $booking->id, 'type' => 'escrow_released'],
                ));
            }
        }
    }
}
