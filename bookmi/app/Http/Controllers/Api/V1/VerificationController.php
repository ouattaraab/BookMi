<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\StoreVerificationRequest;
use App\Http\Resources\VerificationResource;
use App\Services\IdentityVerificationService;
use Illuminate\Http\JsonResponse;

class VerificationController extends BaseController
{
    public function __construct(
        private readonly IdentityVerificationService $service,
    ) {
    }

    public function store(StoreVerificationRequest $request): JsonResponse
    {
        $verification = $this->service->submit(
            $request->user()->id,
            $request->file('document'),
            $request->validated('document_type'),
        );

        return $this->successResponse(
            new VerificationResource($verification),
            201,
        );
    }

    public function showOwn(): JsonResponse
    {
        $verification = $this->service->getByUserId(request()->user()->id);

        if ($verification === null) {
            return $this->errorResponse(
                'VERIFICATION_NOT_FOUND',
                'Aucune vérification trouvée.',
                404,
            );
        }

        return $this->successResponse(
            new VerificationResource($verification),
        );
    }
}
