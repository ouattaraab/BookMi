<?php

namespace App\Repositories\Contracts;

use Illuminate\Pagination\CursorPaginator;

interface FavoriteRepositoryInterface
{
    /**
     * @return CursorPaginator<\App\Models\TalentProfile>
     */
    public function getFavorites(int $userId, int $perPage): CursorPaginator;

    public function addFavorite(int $userId, int $talentProfileId): void;

    public function removeFavorite(int $userId, int $talentProfileId): int;

    public function isFavorite(int $userId, int $talentProfileId): bool;
}
