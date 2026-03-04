<?php

namespace App\Services;

use App\Exceptions\BookmiException;
use App\Models\TalentProfile;
use App\Repositories\Contracts\TalentRepositoryInterface;
use Illuminate\Support\Facades\Cache;

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

        // Resolve category_ids → set primary category_id on the profile row
        $categoryIds = $this->extractCategoryIds($data);
        unset($data['category_ids']);
        if ($categoryIds !== null) {
            $data['category_id'] = $categoryIds[0];
        }

        $data['user_id'] = $userId;
        $data['profile_completion_percentage'] = $this->calculateCompletionFromData($data);

        $profile = $this->repository->create($data);

        // Sync all selected categories in pivot table
        if ($categoryIds !== null) {
            $profile->categories()->sync($categoryIds);
        }

        return $profile;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(TalentProfile $profile, array $data): TalentProfile
    {
        // Resolve category_ids → update primary category_id on the profile row
        $categoryIds = $this->extractCategoryIds($data);
        unset($data['category_ids']);
        if ($categoryIds !== null) {
            $data['category_id'] = $categoryIds[0];
        }

        $merged = array_merge($profile->only(['bio', 'is_verified']), $data);
        $data['profile_completion_percentage'] = $this->calculateCompletionFromData($merged);

        $updated = $this->repository->update($profile, $data);

        // Sync categories in pivot table
        if ($categoryIds !== null) {
            $updated->categories()->sync($categoryIds);
        }

        // Invalider le cache public du profil après modification
        Cache::forget('talents.profile.' . $profile->slug);

        return $updated;
    }

    /**
     * Sync categories for a profile from a raw category_ids list.
     * Updates both the pivot table and the primary category_id column.
     *
     * @param  array<int, int>  $categoryIds
     */
    public function syncCategories(TalentProfile $profile, array $categoryIds): TalentProfile
    {
        $profile->update(['category_id' => $categoryIds[0]]);
        $profile->categories()->sync($categoryIds);
        Cache::forget('talents.profile.' . $profile->slug);

        return $profile->fresh(['category', 'categories']) ?? $profile;
    }

    /**
     * Extract and normalise category_ids from request data.
     * Supports both `category_ids` (new) and legacy `category_id` (single int).
     *
     * @param  array<string, mixed>  $data
     * @return array<int, int>|null
     */
    private function extractCategoryIds(array $data): ?array
    {
        if (isset($data['category_ids']) && is_array($data['category_ids']) && count($data['category_ids']) > 0) {
            return array_values(array_map('intval', $data['category_ids']));
        }

        // Backward compat: single category_id passed (old clients / old tests)
        if (isset($data['category_id']) && ! isset($data['category_ids'])) {
            return [(int) $data['category_id']];
        }

        return null;
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

        if ($profile === null || ! $profile->is_verified) {
            return null;
        }

        $profile->load([
            'category',
            'subcategory',
            'categories',
            'servicePackages'  => fn ($q) => $q->active()->ordered(),
            'portfolioItems'   => fn ($q) => $q->where('is_approved', true)->latest(),
            'receivedReviews'  => fn ($q) => $q->with('reviewer')->where('is_reported', false)->latest(),
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
