<?php

namespace App\Services;

use App\Repositories\Contracts\TalentRepositoryInterface;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchService
{
    private const SORT_COLUMN_MAP = [
        'rating' => 'average_rating',
        'distance' => 'distance_km',
    ];

    private const FILTER_KEYS = [
        'category_id',
        'subcategory_id',
        'min_cachet',
        'max_cachet',
        'city',
        'min_rating',
    ];

    public function __construct(
        private readonly TalentRepositoryInterface $talentRepository,
    ) {
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function searchTalents(
        array $params,
        ?string $sortBy = null,
        ?string $sortDirection = null,
        int $perPage = 20,
    ): CursorPaginator|LengthAwarePaginator {
        $geoParams = null;
        if (isset($params['lat'], $params['lng'], $params['radius'])) {
            $geoParams = [
                'lat' => (float) $params['lat'],
                'lng' => (float) $params['lng'],
                'radius' => (float) $params['radius'],
            ];
        }

        if ($geoParams && $sortBy === null) {
            $sortBy = 'distance_km';
            $sortDirection = 'asc';
        }

        $sortBy = self::SORT_COLUMN_MAP[$sortBy] ?? $sortBy ?? 'created_at';
        $sortDirection = $sortDirection ?? 'desc';

        $filters = array_intersect_key($params, array_flip(self::FILTER_KEYS));

        return $this->talentRepository->searchVerified($filters, $sortBy, $sortDirection, $perPage, $geoParams);
    }
}
