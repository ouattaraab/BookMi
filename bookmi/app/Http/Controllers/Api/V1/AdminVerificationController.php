<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\AdminVerificationResource;
use App\Models\IdentityVerification;
use App\Services\IdentityVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminVerificationController extends BaseController
{
    public function __construct(
        private readonly IdentityVerificationService $service,
    ) {
    }

    /**
     * GET /admin/verifications
     * List pending identity verifications.
     */
    public function index(): JsonResponse
    {
        $verifications = $this->service->getPending();

        return $this->successResponse(AdminVerificationResource::collection($verifications));
    }

    /**
     * GET /admin/verifications/{verification}
     * Show a single verification with user details.
     */
    public function show(IdentityVerification $verification): JsonResponse
    {
        $verification->load(['user', 'reviewer']);

        return $this->successResponse(new AdminVerificationResource($verification));
    }

    /**
     * GET /admin/verifications/{verification}/document
     * Stream the decrypted document for admin review.
     */
    public function document(IdentityVerification $verification): Response
    {
        $content = $this->service->getDocumentContent($verification);

        return response($content, 200, [
            'Content-Type'        => $verification->original_mime ?? 'application/octet-stream',
            'Content-Disposition' => 'attachment',
            'Cache-Control'       => 'no-store, no-cache, private, must-revalidate',
            'Pragma'              => 'no-cache',
        ]);
    }

    /**
     * POST /admin/verifications/{verification}/review
     * Approve or reject a pending identity verification.
     */
    public function review(Request $request, IdentityVerification $verification): JsonResponse
    {
        $data = $request->validate([
            'decision'         => ['required', 'in:approved,rejected'],
            'rejection_reason' => ['required_if:decision,rejected', 'nullable', 'string', 'max:1000'],
        ]);

        $updated = $this->service->review(
            verification:    $verification,
            decision:        $data['decision'],
            reviewerId:      $request->user()->id,
            rejectionReason: $data['rejection_reason'] ?? null,
        );

        $updated->load(['user', 'reviewer']);

        return $this->successResponse(new AdminVerificationResource($updated));
    }
}
