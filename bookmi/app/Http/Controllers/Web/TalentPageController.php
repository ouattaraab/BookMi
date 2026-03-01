<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\TalentFollow;
use App\Models\TalentProfile;
use App\Services\TalentProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TalentPageController extends Controller
{
    public function __construct(
        private readonly TalentProfileService $talentProfileService,
    ) {
    }

    public function show(string $slug): View
    {
        $result = $this->talentProfileService->getPublicProfile($slug);

        if ($result === null) {
            abort(404);
        }

        $isFollowing = auth()->check()
            ? TalentFollow::where('user_id', auth()->id())
                ->where('talent_profile_id', $result['profile']->id)
                ->exists()
            : false;

        return view('web.talent.show', [
            'profile'        => $result['profile'],
            'similarTalents' => $result['similar_talents'],
            'isFollowing'    => $isFollowing,
        ]);
    }

    public function toggleFollow(TalentProfile $profile): RedirectResponse
    {
        $user = auth()->user();
        $existing = TalentFollow::where('user_id', $user->id)
            ->where('talent_profile_id', $profile->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $msg = 'Vous ne suivez plus ' . $profile->stage_name . '.';
        } else {
            TalentFollow::create([
                'user_id'           => $user->id,
                'talent_profile_id' => $profile->id,
            ]);
            $msg = 'Vous suivez maintenant ' . $profile->stage_name . ' !';
        }

        return back()->with('info', $msg);
    }
}
