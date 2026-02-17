<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\BookmiException;
use App\Http\Requests\Api\StoreTalentProfileRequest;
use App\Http\Requests\Api\UpdateTalentProfileRequest;
use App\Http\Resources\TalentProfileResource;
use App\Models\TalentProfile;
use App\Services\TalentProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TalentProfileController extends BaseController
{
    public function __construct(
        private readonly TalentProfileService $service,
    ) {
    }

    public function store(StoreTalentProfileRequest $request): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();
            $profile = $this->service->createProfile($user->id, $request->validated());

            return $this->successResponse(
                new TalentProfileResource($profile->load('category', 'subcategory')),
                201,
            );
        } catch (BookmiException $e) {
            return $this->errorResponse(
                $e->getErrorCode(),
                $e->getMessage(),
                $e->getStatusCode(),
                $e->getDetails(),
            );
        }
    }

    public function showOwn(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $profile = $this->service->getByUserId($user->id);

        if (! $profile) {
            return $this->errorResponse(
                'TALENT_PROFILE_NOT_FOUND',
                'Aucun profil talent trouvé pour cet utilisateur.',
                404,
            );
        }

        return $this->successResponse(
            new TalentProfileResource($profile->load('category', 'subcategory')),
        );
    }

    public function update(UpdateTalentProfileRequest $request, TalentProfile $talentProfile): JsonResponse
    {
        $profile = $this->service->updateProfile($talentProfile, $request->validated());

        return $this->successResponse(
            new TalentProfileResource($profile->load('category', 'subcategory')),
        );
    }

    public function destroy(Request $request, TalentProfile $talentProfile): JsonResponse
    {
        $this->authorize('delete', $talentProfile);

        $this->service->deleteProfile($talentProfile);

        return response()->json(null, 204);
    }
}
