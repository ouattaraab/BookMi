<?php

namespace App\Notifications;

use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceiptClientNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Transaction $transaction,
    ) {
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $transaction = $this->transaction;
        $booking     = $transaction->bookingRequest;

        $clientName  = trim(($booking?->client?->first_name ?? '') . ' ' . ($booking?->client?->last_name ?? '')) ?: 'Client';
        $talentName  = $booking?->talentProfile?->stage_name
            ?? trim(($booking?->talentProfile?->user?->first_name ?? '') . ' ' . ($booking?->talentProfile?->user?->last_name ?? ''))
            ?: 'Talent';
        $packageName = $booking?->servicePackage?->name ?? ($booking?->package_snapshot['name'] ?? '—');
        $eventDate   = $booking?->event_date?->translatedFormat('d F Y') ?? '—';
        $totalAmount = number_format($transaction->amount ?? 0, 0, ',', ' ');
        $reference   = $transaction->idempotency_key ?? $transaction->gateway_reference ?? '—';
        $paidAt      = $transaction->completed_at?->translatedFormat('d F Y à H:i') ?? now()->translatedFormat('d F Y à H:i');

        // Generate PDF receipt inline (small, fast)
        $commissionRate = $booking?->total_amount > 0
            ? round(($booking->commission_amount / $booking->total_amount) * 100)
            : 15;

        $pdfContent = Pdf::loadView('pdf.payment-receipt', [
            'booking'          => $booking,
            'paidAt'           => $paidAt,
            'paymentReference' => $reference,
            'commissionRate'   => $commissionRate,
        ])->setPaper('a4', 'portrait')->output();

        $filename = "recu-bookmi-" . str_pad($booking?->id ?? 0, 6, '0', STR_PAD_LEFT) . ".pdf";

        return (new MailMessage())
            ->subject('Votre reçu de paiement — BookMi')
            ->markdown('emails.payment-receipt-client', [
                'clientName'  => $clientName,
                'talentName'  => $talentName,
                'packageName' => $packageName,
                'eventDate'   => $eventDate,
                'totalAmount' => $totalAmount,
                'reference'   => $reference,
                'actionUrl'   => url('/client/bookings/' . ($booking?->id ?? '')),
            ])
            ->attachData($pdfContent, $filename, ['mime' => 'application/pdf']);
    }
}
