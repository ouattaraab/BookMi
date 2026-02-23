<?php

namespace App\Http\Controllers\Web\Talent;

use App\Http\Controllers\Controller;
use App\Models\BookingRequest;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(): View
    {
        $profile = auth()->user()->talentProfile;
        if (!$profile) {
            return view('talent.coming-soon', ['title' => 'Analytiques', 'description' => 'Configurez votre profil d\'abord.']);
        }

        $monthly = BookingRequest::where('talent_profile_id', $profile->id)
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count, SUM(cachet_amount) as revenue')
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('year', 'month')
            ->orderBy('year')->orderBy('month')
            ->get();

        $stats = [
            'total'     => BookingRequest::where('talent_profile_id', $profile->id)->count(),
            'completed' => BookingRequest::where('talent_profile_id', $profile->id)->where('status', 'completed')->count(),
            'revenue'   => BookingRequest::where('talent_profile_id', $profile->id)->where('status', 'completed')->sum('cachet_amount'),
            'pending'   => BookingRequest::where('talent_profile_id', $profile->id)->where('status', 'pending')->count(),
        ];

        return view('talent.analytics.index', compact('stats', 'monthly'));
    }
}
