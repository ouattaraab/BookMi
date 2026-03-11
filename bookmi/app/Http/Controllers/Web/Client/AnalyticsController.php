<?php

namespace App\Http\Controllers\Web\Client;

use App\Http\Controllers\Controller;
use App\Models\BookingRequest;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $userId = (int) auth()->id();
        $year   = (int) $request->query('year', now()->year);

        // Stats principales
        $totalBookings = BookingRequest::where('client_id', $userId)->count();
        $completedBookings = BookingRequest::where('client_id', $userId)
            ->where('status', 'completed')->count();
        $totalSpent = (int) BookingRequest::where('client_id', $userId)
            ->whereIn('status', ['paid', 'confirmed', 'completed'])
            ->sum('total_amount');

        // Par mois (année sélectionnée)
        $byMonth = BookingRequest::where('client_id', $userId)
            ->whereYear('created_at', $year)
            ->whereIn('status', ['paid', 'confirmed', 'completed'])
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as count, SUM(total_amount) as amount')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        // Par statut
        $byStatus = BookingRequest::where('client_id', $userId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        // Catégories favorites (top 5)
        $topCategories = BookingRequest::where('booking_requests.client_id', $userId)
            ->join('talent_profiles', 'talent_profiles.id', '=', 'booking_requests.talent_profile_id')
            ->join('categories', 'categories.id', '=', 'talent_profiles.category_id')
            ->selectRaw('categories.name as category, COUNT(*) as count')
            ->groupBy('categories.name')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // Avis donnés
        $reviewsGiven    = Review::where('reviewer_id', $userId)->count();
        $avgRatingGiven  = (float) Review::where('reviewer_id', $userId)->avg('rating');

        // Années disponibles (pour sélecteur)
        $availableYears = BookingRequest::where('client_id', $userId)
            ->selectRaw('YEAR(created_at) as y')
            ->groupBy('y')
            ->orderByDesc('y')
            ->pluck('y')
            ->toArray();

        if (empty($availableYears)) {
            $availableYears = [now()->year];
        }

        return view('client.analytics.index', compact(
            'totalBookings',
            'completedBookings',
            'totalSpent',
            'byMonth',
            'byStatus',
            'topCategories',
            'reviewsGiven',
            'avgRatingGiven',
            'year',
            'availableYears'
        ));
    }
}
