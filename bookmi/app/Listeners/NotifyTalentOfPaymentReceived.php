<?php

namespace App\Listeners;

use App\Events\PaymentReceived;
use App\Jobs\SendPushNotification;
use App\Notifications\PaymentReceivedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyTalentOfPaymentReceived implements ShouldQueue
{
    public function handle(PaymentReceived $event): void
    {
        $transaction = $event->transaction->loadMissing([
            'bookingRequest.talentProfile.user',
            'bookingRequest.client',
            'bookingRequest.servicePackage',
        ]);

        $booking = $transaction->bookingRequest;
        $talent  = $booking?->talentProfile?->user;
        $client  = $booking?->client;

        $amount      = number_format($transaction->amount ?? 0, 0, ',', ' ');
        $packageName = $booking?->servicePackage?->name ?? 'prestation';

        // Notify talent (email + push)
        if ($talent) {
            $talent->notify(new PaymentReceivedNotification($transaction));

            SendPushNotification::dispatch(
                $talent->id,
                'Paiement reçu !',
                "{$amount} XOF sécurisé en séquestre — {$packageName}",
                ['booking_id' => $booking?->id, 'type' => 'payment_received'],
            );
        }

        // Notify client (push only — confirmation)
        if ($client) {
            SendPushNotification::dispatch(
                $client->id,
                'Paiement confirmé',
                "Votre paiement de {$amount} XOF a été sécurisé. Réservation confirmée !",
                ['booking_id' => $booking?->id, 'type' => 'payment_confirmed'],
            );
        }
    }
}
