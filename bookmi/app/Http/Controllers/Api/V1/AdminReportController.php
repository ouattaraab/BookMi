<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\AdminReportRequest;
use App\Models\Payout;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminReportController extends BaseController
{
    /**
     * GET /api/v1/admin/reports/financial
     *
     * Streams a UTF-8 CSV (with BOM for Excel compatibility) containing:
     *  - Section TRANSACTIONS  : all payment transactions in the date range
     *  - Section VERSEMENTS    : all talent payouts in the date range
     *  - Section REMBOURSEMENTS: all refunded transactions in the date range
     *
     * Query params:
     *   start_date  (required) YYYY-MM-DD
     *   end_date    (required) YYYY-MM-DD
     *   format      (optional, default: csv)
     */
    public function financial(AdminReportRequest $request): StreamedResponse
    {
        $startDate = Carbon::parse($request->validated('start_date'))->startOfDay();
        $endDate   = Carbon::parse($request->validated('end_date'))->endOfDay();

        $filename = 'rapport-financier-' . $startDate->format('Y-m-d') . '-au-' . $endDate->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($startDate, $endDate) {
            $output = fopen('php://output', 'w');

            try {
                // UTF-8 BOM for Excel
                fwrite($output, "\xEF\xBB\xBF");

            // ── Section 1 : Transactions ──────────────────────────────────────
            fputcsv($output, ['=== TRANSACTIONS ==='], ';');
            fputcsv($output, ['ID', 'Date', 'Booking ID', 'Montant XOF', 'Commission XOF', 'Statut', 'Méthode', 'Passerelle', 'Référence Gateway'], ';');

            $totalTransactions = 0;
            $totalCommissions  = 0;

            Transaction::with('escrowHold')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->chunkById(100, function ($transactions) use ($output, &$totalTransactions, &$totalCommissions) {
                    foreach ($transactions as $tx) {
                        $commission = $tx->escrowHold?->commission_amount ?? 0;
                        $totalTransactions += $tx->amount;
                        $totalCommissions  += $commission;

                        fputcsv($output, [
                            $tx->id,
                            $tx->created_at->format('Y-m-d H:i'),
                            $tx->booking_request_id,
                            $tx->amount,
                            $commission,
                            $tx->status->value,
                            is_string($tx->payment_method) ? $tx->payment_method : $tx->payment_method->value,
                            $tx->gateway,
                            $tx->gateway_reference ?? '',
                        ], ';');
                    }
                });

            fputcsv($output, ['', '', 'TOTAL', $totalTransactions, $totalCommissions], ';');
            fputcsv($output, [], ';');

            // ── Section 2 : Versements (Payouts) ─────────────────────────────
            fputcsv($output, ['=== VERSEMENTS ==='], ';');
            fputcsv($output, ['ID', 'Date', 'Talent', 'Montant XOF', 'Méthode', 'Statut', 'Référence Gateway'], ';');

            $totalPayouts = 0;

            Payout::with('talentProfile')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->chunkById(100, function ($payouts) use ($output, &$totalPayouts) {
                    foreach ($payouts as $p) {
                        $totalPayouts += $p->amount;

                        fputcsv($output, [
                            $p->id,
                            $p->created_at->format('Y-m-d H:i'),
                            $p->talentProfile?->stage_name ?? '',
                            $p->amount,
                            is_string($p->payout_method) ? $p->payout_method : ($p->payout_method?->value ?? ''),
                            $p->status->value,
                            $p->gateway_reference ?? '',
                        ], ';');
                    }
                });

            fputcsv($output, ['', '', 'TOTAL', $totalPayouts], ';');
            fputcsv($output, [], ';');

            // ── Section 3 : Remboursements ────────────────────────────────────
            fputcsv($output, ['=== REMBOURSEMENTS ==='], ';');
            fputcsv($output, ['ID Transaction', 'Date Remboursement', 'Booking ID', 'Montant Remboursé XOF', 'Motif', 'Référence Remboursement'], ';');

            $totalRefunds = 0;

            Transaction::whereNotNull('refunded_at')
                ->whereBetween('refunded_at', [$startDate, $endDate])
                ->chunkById(100, function ($transactions) use ($output, &$totalRefunds) {
                    foreach ($transactions as $tx) {
                        $amount = $tx->refund_amount ?? 0;
                        $totalRefunds += $amount;

                        fputcsv($output, [
                            $tx->id,
                            $tx->refunded_at->format('Y-m-d H:i'),
                            $tx->booking_request_id,
                            $amount,
                            $tx->refund_reason ?? '',
                            $tx->refund_reference ?? '',
                        ], ';');
                    }
                });

            fputcsv($output, ['', '', 'TOTAL', $totalRefunds], ';');
            } finally {
                fclose($output);
            }
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
