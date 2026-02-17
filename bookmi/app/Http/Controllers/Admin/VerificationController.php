<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewVerificationRequest;
use App\Http\Resources\AdminVerificationResource;
use App\Models\IdentityVerification;
use App\Services\IdentityVerificationService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VerificationController extends Controller
{
    public function __construct(
        private readonly IdentityVerificationService $service,
    ) {
    }

    public function index(): JsonResponse
    {
        $verifications = $this->service->getPending();

        return response()->json([
            'data' => AdminVerificationResource::collection($verifications),
        ]);
    }

    public function show(IdentityVerification $verification): JsonResponse
    {
        $verification->load(['user', 'reviewer']);

        return response()->json([
            'data' => new AdminVerificationResource($verification),
        ]);
    }

    public function document(IdentityVerification $verification): StreamedResponse
    {
        $plaintext = $this->service->getDocumentContent($verification);

        return response()->stream(
            function () use ($plaintext) {
                echo $plaintext;
            },
            200,
            [
                'Content-Type' => $verification->original_mime,
                'Content-Disposition' => 'attachment',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
            ],
        );
    }

    public function review(ReviewVerificationRequest $request, IdentityVerification $verification): JsonResponse
    {
        $verification = $this->service->review(
            $verification,
            $request->validated('decision'),
            $request->user()->id,
            $request->validated('rejection_reason'),
        );

        $verification->load(['user', 'reviewer']);

        return response()->json([
            'data' => new AdminVerificationResource($verification),
        ]);
    }
}
