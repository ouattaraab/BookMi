<?php

namespace App\Http\Controllers\Web\Talent;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\BookingRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EarningsController extends Controller
{
    public function index(Request $request): View
    {
        $profile = auth()->user()->talentProfile;

        if (! $profile) {
            return view('talent.coming-soon', [
                'title'       => 'Mes Revenus',
                'description' => 'Configurez votre profil talent pour accéder à vos revenus.',
            ]);
        }

        // ── Financial summary ──────────────────────────────────────────────
        $revenusLiberes = (int) BookingRequest::where('talent_profile_id', $profile->id)
            ->where('status', BookingStatus::Completed->value)
            ->sum('cachet_amount');

        $totalCachetsActifs = (int) BookingRequest::where('talent_profile_id', $profile->id)
            ->whereIn('status', [
                BookingStatus::Paid->value,
                BookingStatus::Confirmed->value,
                BookingStatus::Completed->value,
            ])
            ->sum('cachet_amount');

        $revenusGlobaux = (int) BookingRequest::where('talent_profile_id', $profile->id)
            ->whereNotIn('status', [
                BookingStatus::Cancelled->value,
                BookingStatus::Rejected->value,
                BookingStatus::Pending->value,
            ])
            ->sum('cachet_amount');

        $soldeCompte = $profile->available_balance ?? 0;

        // ── Paginated earnings history ─────────────────────────────────────
        $earnings = BookingRequest::where('talent_profile_id', $profile->id)
            ->where('status', BookingStatus::Completed->value)
            ->with(['client:id,first_name,last_name'])
            ->orderByDesc('event_date')
            ->paginate(20);

        $summary = compact('revenusLiberes', 'totalCachetsActifs', 'revenusGlobaux', 'soldeCompte');

        return view('talent.earnings.index', compact('summary', 'earnings'));
    }
}
