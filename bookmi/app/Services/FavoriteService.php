<?php

namespace App\Services;

use App\Exceptions\BookmiException;
use App\Models\TalentProfile;
use App\Models\User;
use App\Repositories\Contracts\FavoriteRepositoryInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Pagination\CursorPaginator;

class FavoriteService
{
    public function __construct(
        private readonly FavoriteRepositoryInterface $repository,
    ) {
    }

    /**
     * @return CursorPaginator<TalentProfile>
     */
    public function getFavorites(User $user, int $perPage = 20): CursorPaginator
    {
        return $this->repository->getFavorites($user->id, $perPage);
    }

    public function addFavorite(User $user, int $talentProfileId): TalentProfile
    {
        $talent = TalentProfile::where('id', $talentProfileId)
            ->whereNull('deleted_at')
            ->first();

        if (! $talent) {
            throw new BookmiException(
                'TALENT_NOT_FOUND',
                'Le talent demandé est introuvable.',
                404,
            );
        }

        if (! $talent->is_verified) {
            throw new BookmiException(
                'TALENT_NOT_FOUND',
                'Le talent demandé est introuvable.',
                404,
            );
        }

        try {
            $this->repository->addFavorite($user->id, $talentProfileId);
        } catch (UniqueConstraintViolationException) {
            throw new BookmiException(
                'ALREADY_FAVORITED',
                'Ce talent est déjà dans vos favoris.',
                409,
            );
        }

        /** @var TalentProfile */
        return $user->favorites()
            ->where('talent_profile_id', $talentProfileId)
            ->with(['category'])
            ->first();
    }

    public function removeFavorite(User $user, int $talentProfileId): void
    {
        $removed = $this->repository->removeFavorite($user->id, $talentProfileId);

        if ($removed === 0) {
            throw new BookmiException(
                'FAVORITE_NOT_FOUND',
                'Ce talent n\'est pas dans vos favoris.',
                404,
            );
        }
    }

    public function isFavorite(User $user, int $talentProfileId): bool
    {
        return $this->repository->isFavorite($user->id, $talentProfileId);
    }
}
