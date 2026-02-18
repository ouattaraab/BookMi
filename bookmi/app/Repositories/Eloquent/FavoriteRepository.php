<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\FavoriteRepositoryInterface;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;

class FavoriteRepository implements FavoriteRepositoryInterface
{
    /**
     * @return CursorPaginator<\App\Models\TalentProfile>
     */
    public function getFavorites(int $userId, int $perPage): CursorPaginator
    {
        /** @var CursorPaginator<\App\Models\TalentProfile> */
        return User::findOrFail($userId)
            ->favorites()
            ->verified()
            ->with(['category'])
            ->orderByPivot('created_at', 'desc')
            ->cursorPaginate($perPage);
    }

    public function addFavorite(int $userId, int $talentProfileId): void
    {
        DB::table('user_favorites')->insert([
            'user_id' => $userId,
            'talent_profile_id' => $talentProfileId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function removeFavorite(int $userId, int $talentProfileId): int
    {
        return DB::table('user_favorites')
            ->where('user_id', $userId)
            ->where('talent_profile_id', $talentProfileId)
            ->delete();
    }

    public function isFavorite(int $userId, int $talentProfileId): bool
    {
        return DB::table('user_favorites')
            ->where('user_id', $userId)
            ->where('talent_profile_id', $talentProfileId)
            ->exists();
    }
}
