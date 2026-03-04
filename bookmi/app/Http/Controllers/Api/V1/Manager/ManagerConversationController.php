<?php

namespace App\Http\Controllers\Api\V1\Manager;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use App\Models\TalentProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ManagerConversationController extends BaseController
{
    /**
     * GET /api/v1/manager/conversations
     * Returns all conversations related to the manager's managed talents.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var \App\Models\User $manager */
        $manager = $request->user();

        // Get all talent_profile_ids managed by this manager
        $talentProfileIds = TalentProfile::whereHas(
            'managers',
            fn ($q) => $q->where('users.id', $manager->id),
        )->pluck('id');

        $conversations = Conversation::whereIn('talent_profile_id', $talentProfileIds)
            ->with([
                'client:id,first_name,last_name,email',
                'talentProfile:id,stage_name,user_id',
                'talentProfile.user:id,first_name,last_name',
                'lastMessage',
            ])
            ->orderByDesc('updated_at')
            ->paginate(20);

        return ConversationResource::collection($conversations);
    }
}
