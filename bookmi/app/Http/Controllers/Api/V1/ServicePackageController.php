<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\StoreServicePackageRequest;
use App\Http\Requests\Api\UpdateServicePackageRequest;
use App\Http\Resources\ServicePackageResource;
use App\Models\ServicePackage;
use App\Services\ServicePackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServicePackageController extends BaseController
{
    public function __construct(
        private readonly ServicePackageService $service,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $profile = $user->talentProfile;

        if (! $profile) {
            return $this->errorResponse(
                'TALENT_PROFILE_NOT_FOUND',
                'Vous devez créer un profil talent avant de gérer vos packages.',
                404,
            );
        }

        $packages = $this->service->getPackagesForTalent($profile);

        return $this->successResponse(
            ServicePackageResource::collection($packages),
        );
    }

    public function store(StoreServicePackageRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $profile = $user->talentProfile;

        if (! $profile) {
            return $this->errorResponse(
                'TALENT_PROFILE_NOT_FOUND',
                'Vous devez créer un profil talent avant de gérer vos packages.',
                404,
            );
        }

        $package = $this->service->createPackage($profile, $request->validated());

        return $this->successResponse(
            new ServicePackageResource($package),
            201,
        );
    }

    public function update(UpdateServicePackageRequest $request, ServicePackage $servicePackage): JsonResponse
    {
        $this->authorize('update', $servicePackage);

        $package = $this->service->updatePackage($servicePackage, $request->validated());

        return $this->successResponse(
            new ServicePackageResource($package),
        );
    }

    public function destroy(Request $request, ServicePackage $servicePackage): JsonResponse
    {
        $this->authorize('delete', $servicePackage);

        $this->service->deletePackage($servicePackage);

        return response()->json(null, 204);
    }
}
