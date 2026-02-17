<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\SearchTalentRequest;
use App\Http\Resources\TalentDetailResource;
use App\Http\Resources\TalentResource;
use App\Services\SearchService;
use App\Services\TalentProfileService;
use Illuminate\Http\JsonResponse;

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

    public function show(string $slug): JsonResponse
    {
        $result = $this->talentProfileService->getPublicProfile($slug);

        if ($result === null) {
            return $this->errorResponse(
                code: 'TALENT_NOT_FOUND',
                message: 'Le profil talent demandé est introuvable.',
                statusCode: 404,
            );
        }

        return $this->successResponse(
            data: new TalentDetailResource($result['profile']),
            meta: [
                'similar_talents' => TalentResource::collection($result['similar_talents']),
            ],
        );
    }
}
