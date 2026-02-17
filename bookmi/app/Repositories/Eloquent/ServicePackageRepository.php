<?php

namespace App\Repositories\Eloquent;

use App\Models\ServicePackage;
use App\Repositories\Contracts\ServicePackageRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ServicePackageRepository implements ServicePackageRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ServicePackage
    {
        $package = ServicePackage::create($data);

        return $package->fresh() ?? $package;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ServicePackage $package, array $data): ServicePackage
    {
        $package->update($data);

        return $package->fresh() ?? $package;
    }

    public function delete(ServicePackage $package): bool
    {
        return (bool) $package->delete();
    }

    public function findById(int $id): ?ServicePackage
    {
        return ServicePackage::find($id);
    }

    /**
     * @return Collection<int, ServicePackage>
     */
    public function findByTalentProfileId(int $talentProfileId): Collection
    {
        return ServicePackage::where('talent_profile_id', $talentProfileId)->get();
    }

    /**
     * @return Collection<int, ServicePackage>
     */
    public function findActiveByTalentProfileId(int $talentProfileId): Collection
    {
        return ServicePackage::where('talent_profile_id', $talentProfileId)
            ->active()
            ->ordered()
            ->get();
    }
}
