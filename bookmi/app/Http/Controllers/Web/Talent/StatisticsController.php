<?php

namespace App\Http\Controllers\Web\Talent;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\BookingRequest;
use App\Models\ProfileView;
use Illuminate\View\View;

class StatisticsController extends Controller
{
    public function index(): View
    {
        $profile = auth()->user()->talentProfile;

        if (! $profile) {
            return view('talent.coming-soon', [
                'title'       => 'Statistiques',
                'description' => 'Configurez votre profil talent pour accéder aux statistiques.',
            ]);
        }

        // ── Profile views ──────────────────────────────────────────────────
        $profileViews = [
            'today' => ProfileView::where('talent_profile_id', $profile->id)->whereDate('viewed_at', today())->count(),
            'week'  => ProfileView::where('talent_profile_id', $profile->id)->where('viewed_at', '>=', now()->startOfWeek())->count(),
            'month' => ProfileView::where('talent_profile_id', $profile->id)->where('viewed_at', '>=', now()->startOfMonth())->count(),
            'total' => ProfileView::where('talent_profile_id', $profile->id)->count(),
        ];

        // ── Financial data (completed bookings) ────────────────────────────
        $now              = now();
        $startOfMonth     = $now->copy()->startOfMonth();
        $startOfPrevMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfPrevMonth   = $now->copy()->subMonth()->endOfMonth();

        $base = BookingRequest::where('talent_profile_id', $profile->id)
            ->where('status', BookingStatus::Completed->value);

        $revenusTotal         = (int) (clone $base)->sum('cachet_amount');
        $revenusMoisCourant   = (int) (clone $base)->where('updated_at', '>=', $startOfMonth)->sum('cachet_amount');
        $revenusMoisPrecedent = (int) (clone $base)->whereBetween('updated_at', [$startOfPrevMonth, $endOfPrevMonth])->sum('cachet_amount');
        $nombrePrestations    = (clone $base)->count();
        $cachetMoyen          = $nombrePrestations > 0 ? (int) round($revenusTotal / $nombrePrestations) : 0;

        $comparaison = 0.0;
        if ($revenusMoisPrecedent > 0) {
            $comparaison = round(($revenusMoisCourant - $revenusMoisPrecedent) / $revenusMoisPrecedent * 100, 1);
        } elseif ($revenusMoisCourant > 0) {
            $comparaison = 100.0;
        }

        // ── Last 6 months chart data ───────────────────────────────────────
        $sixMonthsAgo = $now->copy()->subMonths(5)->startOfMonth();
        $rawBookings  = (clone $base)->where('updated_at', '>=', $sixMonthsAgo)->select(['cachet_amount', 'updated_at'])->get();

        $monthlyMap = [];
        foreach ($rawBookings as $b) {
            $key              = \Carbon\Carbon::parse($b->updated_at)->format('Y-m');
            $monthlyMap[$key] = ($monthlyMap[$key] ?? 0) + $b->cachet_amount;
        }

        $mensuels = [];
        for ($i = 5; $i >= 0; $i--) {
            $key        = $now->copy()->subMonths($i)->format('Y-m');
            $mensuels[] = ['mois' => $key, 'revenus' => (int) ($monthlyMap[$key] ?? 0)];
        }

        // ── Booking stats ──────────────────────────────────────────────────
        $bookingStats = [
            'total'     => BookingRequest::where('talent_profile_id', $profile->id)->count(),
            'completed' => BookingRequest::where('talent_profile_id', $profile->id)->where('status', BookingStatus::Completed->value)->count(),
            'confirmed' => BookingRequest::where('talent_profile_id', $profile->id)->where('status', 'confirmed')->count(),
            'pending'   => BookingRequest::where('talent_profile_id', $profile->id)->where('status', BookingStatus::Pending->value)->count(),
            'accepted'  => BookingRequest::where('talent_profile_id', $profile->id)->where('status', 'accepted')->count(),
        ];

        // ── Monthly booking activity table (last 6 months) ────────────────
        $monthly = BookingRequest::where('talent_profile_id', $profile->id)
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count, SUM(cachet_amount) as revenue')
            ->where('created_at', '>=', $now->copy()->subMonths(6))
            ->groupBy('year', 'month')
            ->orderBy('year')->orderBy('month')
            ->get();

        $financial = compact(
            'revenusTotal',
            'revenusMoisCourant',
            'revenusMoisPrecedent',
            'comparaison',
            'nombrePrestations',
            'cachetMoyen',
            'mensuels'
        );

        return view('talent.statistics.index', compact('profileViews', 'financial', 'bookingStats', 'monthly'));
    }
}
