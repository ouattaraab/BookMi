<?php

namespace App\Http\Controllers\Web\Client;

use App\Http\Controllers\Controller;
use App\Models\TalentFollow;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $subscriptions = TalentFollow::where('user_id', $user->id)
            ->with(['talentProfile:id,stage_name,slug,profile_photo,city,category_id,visibility_score',
                'talentProfile.category:id,name'])
            ->withCount('talentProfile')
            ->orderByDesc('created_at')
            ->get();

        return view('client.subscriptions.index', compact('subscriptions'));
    }

    public function destroy(int $talentProfileId): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        TalentFollow::where('user_id', $user->id)
            ->where('talent_profile_id', $talentProfileId)
            ->delete();

        return back()->with('success', 'Vous ne suivez plus cet artiste.');
    }
}
