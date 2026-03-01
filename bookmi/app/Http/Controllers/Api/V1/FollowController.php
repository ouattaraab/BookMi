<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\TalentFollow;
use App\Models\TalentProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowController extends BaseController
{
    /**
     * POST /talents/{talentProfileId}/follow
     * Subscribe to talent updates (idempotent).
     */
    public function store(Request $request, int $talentProfileId): JsonResponse
    {
        $talent = TalentProfile::findOrFail($talentProfileId);

        TalentFollow::firstOrCreate([
            'user_id'           => $request->user()->id,
            'talent_profile_id' => $talent->id,
        ]);

        return $this->successResponse([
            'is_following'   => true,
            'followers_count' => $talent->followers()->count(),
        ]);
    }

    /**
     * DELETE /talents/{talentProfileId}/follow
     * Unsubscribe from talent updates.
     */
    public function destroy(Request $request, int $talentProfileId): JsonResponse
    {
        TalentFollow::where('user_id', $request->user()->id)
            ->where('talent_profile_id', $talentProfileId)
            ->delete();

        $followersCount = TalentFollow::where('talent_profile_id', $talentProfileId)->count();

        return $this->successResponse([
            'is_following'   => false,
            'followers_count' => $followersCount,
        ]);
    }

    /**
     * GET /talents/{talentProfileId}/follow
     * Check current follow status.
     */
    public function check(Request $request, int $talentProfileId): JsonResponse
    {
        $isFollowing = TalentFollow::where('user_id', $request->user()->id)
            ->where('talent_profile_id', $talentProfileId)
            ->exists();

        $followersCount = TalentFollow::where('talent_profile_id', $talentProfileId)->count();

        return $this->successResponse([
            'is_following'   => $isFollowing,
            'followers_count' => $followersCount,
        ]);
    }
}
