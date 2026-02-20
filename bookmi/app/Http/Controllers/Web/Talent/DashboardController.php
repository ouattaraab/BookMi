<?php

namespace App\Http\Controllers\Web\Talent;

use App\Http\Controllers\Controller;
use App\Models\BookingRequest;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $profile = $user->talentProfile;

        if (! $profile) {
            return view('talent.dashboard', [
                'bookings' => collect(),
                'stats'    => ['total' => 0, 'pending' => 0, 'confirmed' => 0, 'completed' => 0, 'revenue' => 0],
                'profile'  => null,
            ]);
        }

        $bookings = BookingRequest::where('talent_profile_id', $profile->id)
            ->with('client')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $stats = [
            'total'     => BookingRequest::where('talent_profile_id', $profile->id)->count(),
            'pending'   => BookingRequest::where('talent_profile_id', $profile->id)->where('status', 'pending')->count(),
            'confirmed' => BookingRequest::where('talent_profile_id', $profile->id)->where('status', 'confirmed')->count(),
            'completed' => BookingRequest::where('talent_profile_id', $profile->id)->where('status', 'completed')->count(),
            'revenue'   => BookingRequest::where('talent_profile_id', $profile->id)
                ->where('status', 'completed')
                ->sum('cachet_amount'),
        ];

        return view('talent.dashboard', compact('bookings', 'stats', 'profile'));
    }
}
