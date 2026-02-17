<?php

namespace App\Repositories\Contracts;

use App\Models\TalentProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;

interface TalentRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string, float>|null  $geoParams
     */
    public function searchVerified(array $filters, string $sortBy, string $sortDirection, int $perPage, ?array $geoParams = null): CursorPaginator|LengthAwarePaginator;

    public function find(int $id): ?TalentProfile;

    public function findBySlug(string $slug): ?TalentProfile;

    public function findByUserId(int $userId): ?TalentProfile;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TalentProfile;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TalentProfile $profile, array $data): TalentProfile;

    public function delete(TalentProfile $profile): bool;

    /**
     * @return Collection<int, TalentProfile>
     */
    public function findSimilar(TalentProfile $profile, int $limit = 5): Collection;
}
