<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\FavoriteResource;
use App\Services\FavoriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends BaseController
{
    public function __construct(
        private readonly FavoriteService $favoriteService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);
        $favorites = $this->favoriteService->getFavorites($request->user(), $perPage);

        $favorites->through(fn ($talent) => new FavoriteResource($talent));

        return $this->paginatedResponse($favorites);
    }

    public function store(Request $request, int $talentProfileId): JsonResponse
    {
        $favorite = $this->favoriteService->addFavorite($request->user(), $talentProfileId);

        return $this->successResponse(new FavoriteResource($favorite), 201);
    }

    public function destroy(Request $request, int $talentProfileId): JsonResponse
    {
        $this->favoriteService->removeFavorite($request->user(), $talentProfileId);

        return response()->json(null, 204);
    }

    public function check(Request $request, int $talentProfileId): JsonResponse
    {
        $isFavorite = $this->favoriteService->isFavorite($request->user(), $talentProfileId);

        return $this->successResponse(['is_favorite' => $isFavorite]);
    }
}
