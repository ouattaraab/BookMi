<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\BookingRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Handles short-lived PDF downloads (receipt & contract) via single-use tokens.
 *
 * Flow:
 *  1. Authenticated user hits /booking_requests/{id}/receipt  or /booking_requests/{id}/contract-url
 *     → server generates a UUID token, stores metadata in cache for 10 minutes
 *     → returns { download_url: "https://…/api/v1/dl/{token}" }
 *
 *  2. Flutter opens that URL in the external browser (url_launcher).
 *     The endpoint is public but the token is single-use and expires in 10 minutes.
 *
 *  3. This controller validates the token, generates/reads the PDF, streams it,
 *     then deletes the token from cache.
 */
class DownloadController extends BaseController
{
    /**
     * GET /api/v1/dl/{token}
     * Public — validates a single-use download token and serves the PDF.
     */
    public function serve(string $token): Response
    {
        $data = Cache::get("pdf_download:{$token}");

        if (! $data) {
            abort(410, 'Ce lien de téléchargement a expiré ou est invalide. Veuillez en générer un nouveau depuis l\'application.');
        }

        // Générer d'abord, invalider le token ensuite (usage unique).
        // L'ordre inverse supprimerait le token même si la génération échouait.
        $response = match ($data['type']) {
            'receipt'  => $this->serveReceipt((int) $data['booking_id']),
            'contract' => $this->serveContract((int) $data['booking_id']),
            default    => abort(400, 'Type de document inconnu.'),
        };

        Cache::forget("pdf_download:{$token}");

        return $response;
    }

    // ── Private helpers ────────────────────────────────────────────────────

    private function serveReceipt(int $bookingId): Response
    {
        $booking = BookingRequest::with([
            'client:id,first_name,last_name,email',
            'talentProfile:id,stage_name,user_id',
            'talentProfile.user:id,email',
            'servicePackage:id,name',
            'transactions' => fn ($q) => $q->where('status', 'succeeded')->latest('completed_at')->limit(1),
        ])->findOrFail($bookingId);

        $transaction    = $booking->transactions->first();
        $reference      = $transaction?->idempotency_key ?? $transaction?->gateway_reference ?? '—';
        $paidAt         = $transaction?->completed_at?->translatedFormat('d F Y à H:i')
            ?? $booking->updated_at?->translatedFormat('d F Y à H:i')
            ?? now()->translatedFormat('d F Y à H:i');
        $commissionRate = $booking->total_amount > 0
            ? (int) round(($booking->commission_amount / $booking->total_amount) * 100)
            : 15;

        $pdf      = Pdf::loadView('pdf.payment-receipt', [
            'booking'          => $booking,
            'paidAt'           => $paidAt,
            'paymentReference' => $reference,
            'commissionRate'   => $commissionRate,
        ])->setPaper('a4', 'portrait');

        $filename = 'recu-bookmi-' . str_pad((string) $bookingId, 6, '0', STR_PAD_LEFT) . '.pdf';

        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function serveContract(int $bookingId): Response
    {
        $booking = BookingRequest::findOrFail($bookingId);

        if (! $booking->contract_path || ! Storage::disk('local')->exists($booking->contract_path)) {
            abort(404, 'Le contrat n\'est pas encore disponible. Veuillez réessayer dans quelques instants.');
        }

        $content  = Storage::disk('local')->get($booking->contract_path);
        $filename = 'contrat-bookmi-' . str_pad((string) $bookingId, 6, '0', STR_PAD_LEFT) . '.pdf';

        return response($content, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
