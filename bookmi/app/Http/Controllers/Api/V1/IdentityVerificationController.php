<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\VerificationStatus;
use App\Models\IdentityVerification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IdentityVerificationController extends BaseController
{
    /**
     * GET /api/v1/me/identity/status
     * Returns the current identity verification status for the authenticated user.
     */
    public function status(Request $request): JsonResponse
    {
        $user         = $request->user();
        $verification = IdentityVerification::where('user_id', $user->id)
            ->latest()
            ->first();

        if (! $verification) {
            return $this->successResponse([
                'document_status'  => null,
                'selfie_submitted' => false,
                'overall_status'   => 'not_submitted',
                'rejection_reason' => null,
            ]);
        }

        return $this->successResponse([
            'document_status'  => $verification->verification_status->value,
            'selfie_submitted' => ! is_null($verification->selfie_path),
            'overall_status'   => $verification->verification_status->value,
            'rejection_reason' => $verification->rejection_reason,
        ]);
    }

    /**
     * POST /api/v1/me/identity/document
     * Submit an identity document for verification.
     */
    public function submitDocument(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_type'   => 'required|in:cni,passeport,permis',
            'document_number' => 'required|string|max:50',
            'document'        => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $user         = $request->user();
        $path         = $request->file('document')->store('identity-documents', 'local');
        $mime         = $request->file('document')->getMimeType();

        // Create or update (replace) the pending verification for this user
        $verification = IdentityVerification::updateOrCreate(
            [
                'user_id'             => $user->id,
                'verification_status' => VerificationStatus::PENDING,
            ],
            [
                'document_type'       => $validated['document_type'],
                'document_number'     => $validated['document_number'],
                'stored_path'         => $path,
                'original_mime'       => $mime,
                'verification_status' => VerificationStatus::PENDING,
                'selfie_path'         => null,
            ]
        );

        return $this->successResponse([
            'id'               => $verification->id,
            'document_status'  => $verification->verification_status->value,
            'selfie_submitted' => false,
            'message'          => 'Document soumis. En attente de validation.',
        ], 201);
    }

    /**
     * POST /api/v1/me/identity/selfie
     * Submit a selfie to complete the identity verification.
     */
    public function submitSelfie(Request $request): JsonResponse
    {
        $request->validate([
            'selfie' => 'required|file|mimes:jpg,jpeg,png|max:5120',
        ]);

        $user         = $request->user();
        $verification = IdentityVerification::where('user_id', $user->id)
            ->where('verification_status', VerificationStatus::PENDING)
            ->latest()
            ->first();

        if (! $verification) {
            return $this->errorResponse(
                'IDENTITY_NO_PENDING_DOCUMENT',
                "Veuillez d'abord soumettre votre pièce d'identité.",
                422
            );
        }

        // Delete old selfie if exists
        if ($verification->selfie_path) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($verification->selfie_path);
        }

        $path = $request->file('selfie')->store('identity-selfies', 'local');
        $verification->update(['selfie_path' => $path]);

        return $this->successResponse([
            'document_status'  => $verification->verification_status->value,
            'selfie_submitted' => true,
            'message'          => 'Selfie soumis. Votre identité est en cours de vérification.',
        ]);
    }
}
