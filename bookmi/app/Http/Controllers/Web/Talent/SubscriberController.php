<?php

namespace App\Http\Controllers\Web\Talent;

use App\Http\Controllers\Controller;
use App\Models\BookingRequest;
use App\Models\TalentFollow;
use App\Models\TalentProfile;
use Illuminate\View\View;

class SubscriberController extends Controller
{
    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $profile = TalentProfile::where('user_id', $user->id)->firstOrFail();

        $subscribers = TalentFollow::where('talent_profile_id', $profile->id)
            ->with('user:id,first_name,last_name,email,phone,created_at')
            ->orderByDesc('created_at')
            ->get();

        // Enrichir chaque abonné avec le nombre de réservations et le total dépensé
        $clientIds = $subscribers->pluck('user_id');

        $bookingStats = BookingRequest::where('talent_profile_id', $profile->id)
            ->whereIn('client_id', $clientIds)
            ->selectRaw('client_id, COUNT(*) as bookings_count, SUM(total_amount) as total_spent')
            ->groupBy('client_id')
            ->get()
            ->keyBy('client_id');

        return view('talent.subscribers.index', compact('subscribers', 'bookingStats', 'profile'));
    }
}
