<?php

namespace App\Filament\Pages;

use App\Models\Payout;
use App\Models\Transaction;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-down';

    protected static ?string $navigationLabel = 'Rapports';

    protected static ?string $title = 'Rapports & Exports';

    protected static ?string $navigationGroup = 'Finances';

    protected static ?int $navigationSort = 15;

    protected static string $view = 'filament.pages.reports-page';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return ($user?->is_admin ?? false) || ($user?->hasAnyRole(['admin_ceo', 'admin_comptable']) ?? false);
    }



    public string $start_date = '';

    public string $end_date = '';

    public function mount(): void
    {
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date   = now()->format('Y-m-d');
    }

    public function downloadFinancial(): StreamedResponse
    {
        $startDate = Carbon::parse($this->start_date)->startOfDay();
        $endDate   = Carbon::parse($this->end_date)->endOfDay();

        $transactions = Transaction::with('bookingRequest.client', 'bookingRequest.talentProfile')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at')
            ->get();

        $payouts = Payout::with('talentProfile.user')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at')
            ->get();

        $filename = 'rapport-financier-' . $this->start_date . '-au-' . $this->end_date . '.csv';

        return response()->streamDownload(function () use ($transactions, $payouts) {
            $out = fopen('php://output', 'w');
            // BOM for Excel
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Transactions
            fputcsv($out, ['=== TRANSACTIONS ==='], ';');
            fputcsv($out, ['Date', 'Réservation', 'Client', 'Talent', 'Méthode', 'Montant (FCFA)', 'Statut', 'Référence'], ';');
            foreach ($transactions as $tx) {
                /** @var \App\Models\Transaction $tx */
                $booking = $tx->bookingRequest;
                /** @var \App\Models\BookingRequest|null $booking */
                fputcsv($out, [
                    $tx->created_at->format('d/m/Y H:i'),
                    '#' . ($tx->booking_request_id ?? '—'),
                    $booking?->client?->email ?? '—',
                    $booking?->talentProfile?->stage_name ?? '—',
                    is_string($tx->payment_method) ? $tx->payment_method : ($tx->payment_method instanceof \BackedEnum ? $tx->payment_method->value : '—'),
                    number_format((int) $tx->amount, 0, ',', ' '),
                    $tx->status instanceof \BackedEnum ? $tx->status->value : (string) $tx->status,
                    $tx->gateway_reference ?? '—',
                ], ';');
            }

            fputcsv($out, [], ';');

            // Versements
            fputcsv($out, ['=== VERSEMENTS TALENTS ==='], ';');
            fputcsv($out, ['Date', 'Talent', 'Montant (FCFA)', 'Statut', 'Méthode paiement'], ';');
            foreach ($payouts as $payout) {
                /** @var \App\Models\Payout $payout */
                $talentProfile = $payout->talentProfile;
                /** @var \App\Models\TalentProfile|null $talentProfile */
                fputcsv($out, [
                    $payout->created_at->format('d/m/Y H:i'),
                    $talentProfile?->stage_name ?? '—',
                    number_format((int) $payout->amount, 0, ',', ' '),
                    $payout->status instanceof \BackedEnum ? $payout->status->value : (string) $payout->status,
                    is_string($talentProfile?->payout_method) ? $talentProfile->payout_method : '—',
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
