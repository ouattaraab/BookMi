<?php

namespace App\Repositories\Contracts;

use App\Models\ServicePackage;
use Illuminate\Database\Eloquent\Collection;

interface ServicePackageRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ServicePackage;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ServicePackage $package, array $data): ServicePackage;

    public function delete(ServicePackage $package): bool;

    public function findById(int $id): ?ServicePackage;

    /**
     * @return Collection<int, ServicePackage>
     */
    public function findByTalentProfileId(int $talentProfileId): Collection;

    /**
     * @return Collection<int, ServicePackage>
     */
    public function findActiveByTalentProfileId(int $talentProfileId): Collection;
}
