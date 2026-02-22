<?php

namespace App\Services;

use App\Exceptions\BookmiException;
use App\Models\TalentProfile;
use App\Repositories\Contracts\TalentRepositoryInterface;

class TalentProfileService
{
    public function __construct(
        private readonly TalentRepositoryInterface $repository,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws BookmiException
     */
    public function createProfile(int $userId, array $data): TalentProfile
    {
        if ($this->repository->findByUserId($userId)) {
            throw new BookmiException(
                'TALENT_ALREADY_HAS_PROFILE',
                'Cet utilisateur possède déjà un profil talent.',
                422,
            );
        }

        $data['user_id'] = $userId;
        $data['profile_completion_percentage'] = $this->calculateCompletionFromData($data);

        return $this->repository->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(TalentProfile $profile, array $data): TalentProfile
    {
        $merged = array_merge($profile->only(['bio', 'is_verified']), $data);
        $data['profile_completion_percentage'] = $this->calculateCompletionFromData($merged);

        return $this->repository->update($profile, $data);
    }

    public function getBySlug(string $slug): ?TalentProfile
    {
        return $this->repository->findBySlug($slug);
    }

    /**
     * @return array{profile: TalentProfile, similar_talents: \Illuminate\Database\Eloquent\Collection<int, TalentProfile>}|null
     */
    public function getPublicProfile(string $slug): ?array
    {
        $profile = $this->repository->findBySlug($slug);

        if ($profile === null) {
            return null;
        }

        $profile->load([
            'category',
            'subcategory',
            'servicePackages' => fn ($q) => $q->active()->ordered(),
            'portfolioItems'  => fn ($q) => $q->where('is_approved', true)->latest(),
        ]);

        $similarTalents = $this->repository->findSimilar($profile);

        return [
            'profile' => $profile,
            'similar_talents' => $similarTalents,
        ];
    }

    public function getByUserId(int $userId): ?TalentProfile
    {
        return $this->repository->findByUserId($userId);
    }

    public function deleteProfile(TalentProfile $profile): bool
    {
        return $this->repository->delete($profile);
    }

    public function recalculateCompletion(TalentProfile $profile): TalentProfile
    {
        $percentage = 0;

        if (! empty($profile->bio)) {
            $percentage += 20;
        }
        if ($profile->is_verified) {
            $percentage += 20;
        }

        // Photo profil (futur) → +20%
        // Portfolio 3+ médias (futur) → +20%

        if ($profile->servicePackages()->where('is_active', true)->exists()) {
            $percentage += 20;
        }

        return $this->repository->update($profile, [
            'profile_completion_percentage' => $percentage,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function calculateCompletionFromData(array $data): int
    {
        $percentage = 0;

        if (! empty($data['bio'])) {
            $percentage += 20;
        }
        if (! empty($data['is_verified'])) {
            $percentage += 20;
        }

        // Photo profil (futur — Story 1.7) → +20%
        // Portfolio 3+ médias (futur) → +20%
        // Au moins 1 package → +20% (géré dans recalculateCompletion)

        return $percentage;
    }
}
