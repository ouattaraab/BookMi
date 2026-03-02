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
use Illuminate\Support\Facades\Cache;

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

        $hasGeo = isset($validated['lat'], $validated['lng']);

        if ($hasGeo) {
            // Cache géo : arrondi à 2 décimales (~1 km) pour regrouper les recherches proches
            // On inclut le numéro de page dans la clé (page non validée mais gérée par le paginator)
            $geoParams = $validated;
            $geoParams['lat'] = round((float) $validated['lat'], 2);
            $geoParams['lng'] = round((float) $validated['lng'], 2);
            $geoParams['_page'] = $request->integer('page', 1);
            $cacheKey = 'talents.search.geo.' . md5(serialize($geoParams));
            $paginator = Cache::remember($cacheKey, 30, function () use ($validated) {
                return $this->searchService->searchTalents(
                    params: $validated,
                    sortBy: $validated['sort_by'] ?? null,
                    sortDirection: $validated['sort_direction'] ?? null,
                    perPage: (int) ($validated['per_page'] ?? 20),
                );
            });
        } else {
            $params = $validated;
            $params['_page'] = $request->integer('page', 1);
            $cacheKey = 'talents.search.' . md5(serialize($params));
            $paginator = Cache::remember($cacheKey, 30, function () use ($validated) {
                return $this->searchService->searchTalents(
                    params: $validated,
                    sortBy: $validated['sort_by'] ?? null,
                    sortDirection: $validated['sort_direction'] ?? null,
                    perPage: (int) ($validated['per_page'] ?? 20),
                );
            });
        }

        $paginator->through(fn ($talent) => new TalentResource($talent));

        return $this->paginatedResponse($paginator);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $cacheKey = 'talents.profile.' . $slug;
        $result = Cache::remember($cacheKey, 60, function () use ($slug) {
            return $this->talentProfileService->getPublicProfile($slug);
        });

        if ($result === null) {
            return $this->errorResponse(
                code: 'TALENT_NOT_FOUND',
                message: 'Le profil talent demandé est introuvable.',
                statusCode: 404,
            );
        }

        // Track profile view — deduplicated par viewer par jour (cache-debounced)
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

        // Debounce via cache : évite 2 requêtes DB par appel si déjà tracké aujourd'hui
        $debounceKey = 'pv.' . $talentProfileId . '.' . $today . '.' . ($viewerId ?? md5($viewerIp));
        if (Cache::has($debounceKey)) {
            return;
        }

        // Transaction + lockForUpdate : évite la race condition exists()+create()
        // en cas de deux requêtes simultanées (même viewer, même jour)
        \Illuminate\Support\Facades\DB::transaction(function () use ($talentProfileId, $today, $viewerId, $viewerIp) {
            $alreadyViewed = ProfileView::where('talent_profile_id', $talentProfileId)
                ->whereDate('viewed_at', $today)
                ->when(
                    $viewerId,
                    fn ($q) => $q->where('viewer_id', $viewerId),
                    fn ($q) => $q->whereNull('viewer_id')->where('viewer_ip', $viewerIp),
                )
                ->lockForUpdate()
                ->exists();

            if (! $alreadyViewed) {
                ProfileView::create([
                    'talent_profile_id' => $talentProfileId,
                    'viewer_id'         => $viewerId,
                    'viewer_ip'         => $viewerIp,
                    'viewed_at'         => now(),
                ]);
            }
        });

        // Mémoriser jusqu'à minuit pour ne plus interroger la DB aujourd'hui
        Cache::put($debounceKey, true, now()->endOfDay());
    }
}
