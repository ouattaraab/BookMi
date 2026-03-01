<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Enums\PayoutStatus;
use App\Models\BookingRequest;
use App\Models\Payout;
use App\Models\TalentProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FinancialDashboardController extends BaseController
{
    /**
     * GET /api/v1/me/financial_dashboard
     *
     * Returns financial summary for the authenticated talent:
     * - Totals (all-time, current month, previous month)
     * - Monthly comparison percentage
     * - Last 6 months breakdown for charts
     */
    public function dashboard(Request $request): JsonResponse
    {
        $talentProfile = TalentProfile::where('user_id', $request->user()->id)->first();

        if (! $talentProfile) {
            return $this->errorResponse('TALENT_PROFILE_NOT_FOUND', 'Aucun profil talent trouvé.', 404);
        }

        $now = now();
        $startOfCurrentMonth = $now->copy()->startOfMonth();
        $startOfPrevMonth    = $now->copy()->subMonth()->startOfMonth();
        $endOfPrevMonth      = $now->copy()->subMonth()->endOfMonth();

        // Base query — only succeeded payouts
        $base = Payout::where('talent_profile_id', $talentProfile->id)
            ->where('status', PayoutStatus::Succeeded->value);

        $revenusTotal          = (int) (clone $base)->sum('amount');
        $revenusMoisCourant    = (int) (clone $base)->where('processed_at', '>=', $startOfCurrentMonth)->sum('amount');
        $revenusMoisPrecedent  = (int) (clone $base)->whereBetween('processed_at', [$startOfPrevMonth, $endOfPrevMonth])->sum('amount');
        $nombrePrestations     = (clone $base)->count();
        $cachetMoyen           = $nombrePrestations > 0 ? (int) round($revenusTotal / $nombrePrestations) : 0;

        $comparaison = 0.0;
        if ($revenusMoisPrecedent > 0) {
            $comparaison = round(($revenusMoisCourant - $revenusMoisPrecedent) / $revenusMoisPrecedent * 100, 1);
        } elseif ($revenusMoisCourant > 0) {
            $comparaison = 100.0;
        }

        // Last 6 months breakdown — single query, grouped in PHP to avoid DB-specific date functions
        $sixMonthsAgo = $now->copy()->subMonths(5)->startOfMonth();

        $rawPayouts = (clone $base)
            ->where('processed_at', '>=', $sixMonthsAgo)
            ->select(['amount', 'processed_at'])
            ->get();

        $monthlyMap = [];
        foreach ($rawPayouts as $p) {
            $key               = \Carbon\Carbon::parse($p->processed_at)->format('Y-m');
            $monthlyMap[$key]  = ($monthlyMap[$key] ?? 0) + $p->amount;
        }

        // Fill all 6 months in order (including months with zero revenue)
        $mensuels = [];
        for ($i = 5; $i >= 0; $i--) {
            $key        = $now->copy()->subMonths($i)->format('Y-m');
            $mensuels[] = ['mois' => $key, 'revenus' => (int) ($monthlyMap[$key] ?? 0)];
        }

        return response()->json([
            'data' => [
                'revenus_total'           => $revenusTotal,
                'revenus_mois_courant'    => $revenusMoisCourant,
                'revenus_mois_precedent'  => $revenusMoisPrecedent,
                'comparaison_pourcentage' => $comparaison,
                'nombre_prestations'      => $nombrePrestations,
                'cachet_moyen'            => $cachetMoyen,
                'devise'                  => 'XOF',
                'mensuels'                => $mensuels,
            ],
        ]);
    }

    /**
     * GET /api/v1/me/payouts
     *
     * Paginated payout history for the authenticated talent (newest first).
     */
    public function payouts(Request $request): JsonResponse
    {
        $talentProfile = TalentProfile::where('user_id', $request->user()->id)->first();

        if (! $talentProfile) {
            return $this->errorResponse('TALENT_PROFILE_NOT_FOUND', 'Aucun profil talent trouvé.', 404);
        }

        $payouts = Payout::where('talent_profile_id', $talentProfile->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => $payouts->items(),
            'meta' => [
                'current_page' => $payouts->currentPage(),
                'last_page'    => $payouts->lastPage(),
                'per_page'     => $payouts->perPage(),
                'total'        => $payouts->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/me/calendar/alerts
     *
     * Returns real-time calendar health for the authenticated talent:
     * - is_overloaded: active bookings ≥ overload_threshold
     * - has_empty_upcoming: no booking in the next 30 days
     */
    public function calendarAlerts(Request $request): JsonResponse
    {
        $talentProfile = TalentProfile::where('user_id', $request->user()->id)->first();

        if (! $talentProfile) {
            return $this->errorResponse('TALENT_PROFILE_NOT_FOUND', 'Aucun profil talent trouvé.', 404);
        }

        $activeBookings = BookingRequest::where('talent_profile_id', $talentProfile->id)
            ->whereIn('status', [BookingStatus::Paid->value, BookingStatus::Confirmed->value])
            ->count();

        $threshold   = (int) ($talentProfile->overload_threshold ?? 5);
        $isOverloaded = $activeBookings >= $threshold;

        $hasUpcoming = BookingRequest::where('talent_profile_id', $talentProfile->id)
            ->whereIn('status', [
                BookingStatus::Pending->value,
                BookingStatus::Accepted->value,
                BookingStatus::Paid->value,
                BookingStatus::Confirmed->value,
            ])
            ->where('event_date', '>=', now()->toDateString())
            ->where('event_date', '<=', now()->addDays(30)->toDateString())
            ->exists();

        return response()->json([
            'data' => [
                'is_overloaded'        => $isOverloaded,
                'active_booking_count' => $activeBookings,
                'overload_threshold'   => $threshold,
                'has_empty_upcoming'   => ! $hasUpcoming,
            ],
        ]);
    }

    /**
     * GET /api/v1/me/earnings/export
     *
     * Returns a UTF-8 CSV file of all completed bookings for the authenticated talent.
     * Optional query param: year (e.g. ?year=2025)
     */
    public function exportEarnings(Request $request): Response|JsonResponse
    {
        $talentProfile = TalentProfile::where('user_id', $request->user()->id)->first();

        if (! $talentProfile) {
            return $this->errorResponse('TALENT_PROFILE_NOT_FOUND', 'Aucun profil talent trouvé.', 404);
        }

        $year = $request->query('year') !== null ? (int) $request->query('year') : null;

        $query = BookingRequest::where('talent_profile_id', $talentProfile->id)
            ->where('status', BookingStatus::Completed->value)
            ->with(['client:id,first_name,last_name'])
            ->orderByDesc('event_date');

        if ($year !== null) {
            $query->whereYear('event_date', $year);
        }

        $bookings = $query->get();

        $filename = 'revenus_' . ($year ?? date('Y')) . '_' . date('Ymd') . '.csv';

        /** @var resource $handle */
        $handle = fopen('php://memory', 'w+');
        fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
        fputcsv($handle, ['Date prestation', 'Client', 'Forfait', 'Cachet (FCFA)', 'Commission (FCFA)', 'Net (FCFA)'], ';');

        foreach ($bookings as $b) {
            $cachet     = (int) $b->cachet_amount;
            $commission = (int) $b->commission_amount;
            $net        = $cachet - $commission;
            $clientName = trim(($b->client->first_name ?? '') . ' ' . ($b->client->last_name ?? ''));
            $packageName = isset($b->package_snapshot['name'])
                ? $b->package_snapshot['name']
                : 'Prestation libre';
            $eventDate  = $b->event_date instanceof \Carbon\Carbon
                ? $b->event_date->format('d/m/Y')
                : (string) $b->event_date;

            fputcsv($handle, [$eventDate, $clientName, $packageName, $cachet, $commission, $net], ';');
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * GET /api/v1/me/earnings
     *
     * Returns paginated list of completed bookings with earnings breakdown.
     */
    public function earnings(Request $request): JsonResponse
    {
        $talentProfile = TalentProfile::where('user_id', $request->user()->id)->first();

        if (! $talentProfile) {
            return $this->errorResponse('TALENT_PROFILE_NOT_FOUND', 'Aucun profil talent trouvé.', 404);
        }

        $perPage = min(max((int) $request->query('per_page', 20), 1), 50);

        $bookings = \App\Models\BookingRequest::where('talent_profile_id', $talentProfile->id)
            ->where('status', \App\Enums\BookingStatus::Completed->value)
            ->with(['client:id,first_name,last_name'])
            ->orderByDesc('event_date')
            ->paginate($perPage);

        // Revenus libérés = cachets des réservations terminées (escrow libéré)
        $revenusLiberes = (int) BookingRequest::where('talent_profile_id', $talentProfile->id)
            ->where('status', BookingStatus::Completed->value)
            ->sum('cachet_amount');

        // Total cachets actifs = paid + confirmed + completed (y compris à venir)
        $totalCachetsActifs = (int) BookingRequest::where('talent_profile_id', $talentProfile->id)
            ->whereIn('status', [
                BookingStatus::Paid->value,
                BookingStatus::Confirmed->value,
                BookingStatus::Completed->value,
            ])
            ->sum('cachet_amount');

        // Revenus globaux = toutes les réservations non annulées/rejetées
        $revenusGlobaux = (int) BookingRequest::where('talent_profile_id', $talentProfile->id)
            ->whereNotIn('status', [
                BookingStatus::Cancelled->value,
                BookingStatus::Rejected->value,
                BookingStatus::Pending->value,
            ])
            ->sum('cachet_amount');

        $totalCommission = (int) BookingRequest::where('talent_profile_id', $talentProfile->id)
            ->where('status', BookingStatus::Completed->value)
            ->sum('commission_amount');

        $items = collect($bookings->items())->map(fn ($b) => [
            'booking_id'    => $b->id,
            'event_date'    => $b->event_date instanceof \Carbon\Carbon
                ? $b->event_date->toDateString()
                : (string) $b->event_date,
            'client_name'   => trim(($b->client?->first_name ?? '') . ' ' . ($b->client?->last_name ?? '')),
            'package_name'  => isset($b->package_snapshot['name'])
                ? $b->package_snapshot['name']
                : 'Prestation libre',
            'cachet_amount' => $b->cachet_amount,
        ]);

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page'        => $bookings->currentPage(),
                'last_page'           => $bookings->lastPage(),
                'per_page'            => $bookings->perPage(),
                'total'               => $bookings->total(),
                'revenus_liberes'     => $revenusLiberes,
                'total_cachets_actifs' => $totalCachetsActifs,
                'revenus_globaux'     => $revenusGlobaux,
                'solde_compte'        => $talentProfile->available_balance ?? 0,
                'devise'              => 'XOF',
            ],
        ]);
    }
}
