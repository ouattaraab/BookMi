<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\SearchTalentRequest;
use App\Http\Resources\TalentDetailResource;
use App\Http\Resources\TalentResource;
use App\Models\AvailabilityAlert;
use App\Models\ProfileView;
use App\Models\TalentProfile;
use App\Services\SearchService;
use App\Services\TalentProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TalentController extends BaseController
{
    public function __construct(
        private readonly SearchService $searchService,
        private readonly TalentProfileService $talentProfileService,
    ) {
    }

    public function index(SearchTalentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $paginator = $this->searchService->searchTalents(
            params: $validated,
            sortBy: $validated['sort_by'] ?? null,
            sortDirection: $validated['sort_direction'] ?? null,
            perPage: (int) ($validated['per_page'] ?? 20),
        );

        $paginator->through(fn ($talent) => new TalentResource($talent));

        return $this->paginatedResponse($paginator);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $result = $this->talentProfileService->getPublicProfile($slug);

        if ($result === null) {
            return $this->errorResponse(
                code: 'TALENT_NOT_FOUND',
                message: 'Le profil talent demandé est introuvable.',
                statusCode: 404,
            );
        }

        // Track profile view — deduplicated per viewer per day
        $this->trackProfileView($request, $result['profile']->id);

        return $this->successResponse(
            data: new TalentDetailResource($result['profile']),
            meta: [
                'similar_talents' => TalentResource::collection($result['similar_talents']),
            ],
        );
    }

    /**
     * POST /api/v1/talents/{talent}/notify-availability
     * Register the authenticated user to be notified when the talent
     * becomes available on a given event_date.
     */
    public function notifyAvailability(Request $request, TalentProfile $talent): JsonResponse
    {
        $validated = $request->validate([
            'event_date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        AvailabilityAlert::firstOrCreate([
            'user_id'           => $request->user()->id,
            'talent_profile_id' => $talent->id,
            'event_date'        => $validated['event_date'],
        ]);

        return $this->successResponse([
            'message' => 'Vous serez notifié lorsque ce talent sera disponible à cette date.',
        ]);
    }

    private function trackProfileView(Request $request, int $talentProfileId): void
    {
        $viewerId = $request->user()?->id;
        $viewerIp = $request->ip();
        $today = now()->toDateString();

        $alreadyViewed = ProfileView::where('talent_profile_id', $talentProfileId)
            ->whereDate('viewed_at', $today)
            ->when(
                $viewerId,
                fn ($q) => $q->where('viewer_id', $viewerId),
                fn ($q) => $q->whereNull('viewer_id')->where('viewer_ip', $viewerIp),
            )
            ->exists();

        if (! $alreadyViewed) {
            ProfileView::create([
                'talent_profile_id' => $talentProfileId,
                'viewer_id'         => $viewerId,
                'viewer_ip'         => $viewerIp,
                'viewed_at'         => now(),
            ]);
        }
    }
}
