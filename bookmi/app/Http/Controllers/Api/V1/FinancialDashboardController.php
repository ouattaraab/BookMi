<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PayoutStatus;
use App\Models\Payout;
use App\Models\TalentProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
