<?php

namespace App\Repositories\Eloquent;

use App\Models\TalentProfile;
use App\Repositories\Contracts\TalentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;

class TalentRepository implements TalentRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string, float>|null  $geoParams
     */
    public function searchVerified(
        array $filters,
        string $sortBy,
        string $sortDirection,
        int $perPage,
        ?array $geoParams = null,
    ): CursorPaginator|LengthAwarePaginator {
        $query = TalentProfile::query()
            ->verified()
            ->with(['category', 'subcategory'])
            ->when(
                isset($filters['category_id']),
                fn ($q) => $q->byCategory((int) $filters['category_id']),
            )
            ->when(
                isset($filters['subcategory_id']),
                fn ($q) => $q->where('subcategory_id', $filters['subcategory_id']),
            )
            ->when(
                isset($filters['min_cachet']),
                fn ($q) => $q->where('cachet_amount', '>=', $filters['min_cachet']),
            )
            ->when(
                isset($filters['max_cachet']),
                fn ($q) => $q->where('cachet_amount', '<=', $filters['max_cachet']),
            )
            ->when(
                isset($filters['city']),
                fn ($q) => $q->byCity((string) $filters['city']),
            )
            ->when(
                isset($filters['min_rating']),
                fn ($q) => $q->where('average_rating', '>=', $filters['min_rating']),
            );

        if ($geoParams) {
            $haversineBindings = [$geoParams['lat'], $geoParams['lat'], $geoParams['lng']];

            $query->selectRaw(
                'talent_profiles.*, ' . TalentProfile::HAVERSINE_SQL . ' AS distance_km',
                $haversineBindings,
            );

            $query->withinRadiusOf(
                $geoParams['lat'],
                $geoParams['lng'],
                $geoParams['radius'],
            );

            return $query
                ->orderBy($sortBy, $sortDirection)
                ->orderBy('id', 'asc')
                ->paginate($perPage);
        }

        return $query
            ->orderBy($sortBy, $sortDirection)
            ->orderBy('id', 'asc')
            ->cursorPaginate($perPage);
    }

    public function find(int $id): ?TalentProfile
    {
        return TalentProfile::find($id);
    }

    public function findBySlug(string $slug): ?TalentProfile
    {
        return TalentProfile::where('slug', $slug)->first();
    }

    public function findByUserId(int $userId): ?TalentProfile
    {
        return TalentProfile::where('user_id', $userId)->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TalentProfile
    {
        return TalentProfile::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TalentProfile $profile, array $data): TalentProfile
    {
        $profile->update($data);

        return $profile->refresh();
    }

    public function delete(TalentProfile $profile): bool
    {
        return (bool) $profile->delete();
    }

    /**
     * @return Collection<int, TalentProfile>
     */
    public function findSimilar(TalentProfile $profile, int $limit = 5): Collection
    {
        return TalentProfile::query()
            ->verified()
            ->where('category_id', $profile->category_id)
            ->where('city', $profile->city)
            ->where('id', '!=', $profile->id)
            ->with(['category', 'subcategory'])
            ->orderBy('average_rating', 'desc')
            ->limit($limit)
            ->get();
    }
}
