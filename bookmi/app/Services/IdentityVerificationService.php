<?php

namespace App\Services;

use App\Enums\VerificationStatus;
use App\Exceptions\BookmiException;
use App\Models\IdentityVerification;
use App\Repositories\Contracts\VerificationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class IdentityVerificationService
{
    public function __construct(
        private readonly VerificationRepositoryInterface $repository,
        private readonly TalentProfileService $talentProfileService,
        private readonly AuditService $auditService,
    ) {
    }

    /**
     * @throws BookmiException
     */
    public function submit(int $userId, UploadedFile $document, string $documentType): IdentityVerification
    {
        $talentProfile = $this->talentProfileService->getByUserId($userId);

        if ($talentProfile === null) {
            throw new BookmiException(
                'VERIFICATION_NO_TALENT_PROFILE',
                "L'utilisateur n'a pas de profil talent.",
                422,
            );
        }

        return DB::transaction(function () use ($userId, $document, $documentType) {
            $existing = $this->repository->findPendingByUserId($userId, lockForUpdate: true);
            if ($existing !== null) {
                throw new BookmiException(
                    'VERIFICATION_ALREADY_PENDING',
                    'Une demande de vérification est déjà en cours.',
                    422,
                );
            }

            $disk = config('bookmi.verification.disk');
            $plaintext = file_get_contents($document->getRealPath());
            $encrypted = Crypt::encrypt($plaintext, serialize: false);
            $storedName = Str::uuid() . '.enc';
            $storedPath = "identity/{$storedName}";
            Storage::disk($disk)->put($storedPath, $encrypted);

            return $this->repository->create([
                'user_id' => $userId,
                'document_type' => $documentType,
                'stored_path' => $storedPath,
                'original_mime' => $document->getMimeType(),
                'verification_status' => VerificationStatus::PENDING,
            ]);
        });
    }

    public function getByUserId(int $userId): ?IdentityVerification
    {
        return $this->repository->findByUserId($userId);
    }

    /**
     * @return Collection<int, IdentityVerification>
     */
    public function getPending(): Collection
    {
        return $this->repository->findPending();
    }

    /**
     * @throws BookmiException
     */
    public function getDocumentContent(IdentityVerification $verification): string
    {
        if ($verification->stored_path === null) {
            throw new BookmiException(
                'VERIFICATION_NOT_FOUND',
                'Le document a été supprimé après vérification.',
                404,
            );
        }

        $disk = config('bookmi.verification.disk');
        $encrypted = Storage::disk($disk)->get($verification->stored_path);

        return Crypt::decrypt($encrypted, unserialize: false);
    }

    /**
     * @throws BookmiException
     */
    public function review(IdentityVerification $verification, string $decision, int $reviewerId, ?string $rejectionReason = null): IdentityVerification
    {
        /** @var VerificationStatus $currentStatus */
        $currentStatus = $verification->verification_status;
        if ($currentStatus->isTerminal()) {
            throw new BookmiException(
                'VERIFICATION_ALREADY_REVIEWED',
                'Cette vérification a déjà été traitée.',
                422,
            );
        }

        $data = [
            'verification_status' => $decision === 'approved' ? VerificationStatus::APPROVED : VerificationStatus::REJECTED,
            'reviewer_id' => $reviewerId,
            'reviewed_at' => now(),
        ];

        if ($decision === 'approved') {
            $data['verified_at'] = now();
        } else {
            $data['rejection_reason'] = $rejectionReason;
        }

        $verification = $this->repository->update($verification, $data);

        $this->deleteDocument($verification);

        if ($decision === 'approved') {
            $talentProfile = $this->talentProfileService->getByUserId($verification->user_id);
            if ($talentProfile !== null) {
                $talentProfile->update(['is_verified' => true]);
                $this->talentProfileService->recalculateCompletion($talentProfile->refresh());
            }
        }

        $this->auditService->log(
            "identity_verification.{$decision}",
            $verification,
            array_filter([
                'decision' => $decision,
                'rejection_reason' => $rejectionReason,
            ]),
        );

        return $verification;
    }

    public function deleteDocument(IdentityVerification $verification): void
    {
        if ($verification->stored_path === null) {
            return;
        }

        $disk = config('bookmi.verification.disk');
        Storage::disk($disk)->delete($verification->stored_path);

        $this->repository->update($verification, ['stored_path' => null]);
    }
}
