<?php

namespace App\Http\Controllers\Web\Manager;

use App\Http\Controllers\Controller;
use App\Models\BookingRequest;
use App\Models\TalentProfile;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        // Talents managed by this manager (via talent_manager pivot table)
        $talentCount = TalentProfile::whereHas('managers', fn ($q) => $q->where('users.id', $user->id))->count();

        $talentProfileIds = TalentProfile::whereHas('managers', fn ($q) => $q->where('users.id', $user->id))
            ->pluck('id');

        $stats = [
            'talents'   => $talentCount,
            'bookings'  => BookingRequest::whereIn('talent_profile_id', $talentProfileIds)->count(),
            'pending'   => BookingRequest::whereIn('talent_profile_id', $talentProfileIds)->where('status', 'pending')->count(),
            'completed' => BookingRequest::whereIn('talent_profile_id', $talentProfileIds)->where('status', 'completed')->count(),
        ];

        return view('manager.dashboard', compact('stats'));
    }
}
