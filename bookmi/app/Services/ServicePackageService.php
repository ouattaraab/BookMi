<?php

namespace App\Services;

use App\Models\ServicePackage;
use App\Models\TalentProfile;
use App\Repositories\Contracts\ServicePackageRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ServicePackageService
{
    public function __construct(
        private readonly ServicePackageRepositoryInterface $repository,
        private readonly TalentProfileService $talentProfileService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPackage(TalentProfile $profile, array $data): ServicePackage
    {
        $data['talent_profile_id'] = $profile->id;

        $package = $this->repository->create($data);

        $this->talentProfileService->recalculateCompletion($profile);

        return $package;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updatePackage(ServicePackage $package, array $data): ServicePackage
    {
        $package = $this->repository->update($package, $data);

        if (array_key_exists('is_active', $data)) {
            $this->talentProfileService->recalculateCompletion($package->talentProfile);
        }

        return $package;
    }

    public function deletePackage(ServicePackage $package): bool
    {
        $profile = $package->talentProfile;

        $result = $this->repository->delete($package);

        $this->talentProfileService->recalculateCompletion($profile);

        return $result;
    }

    /**
     * @return Collection<int, ServicePackage>
     */
    public function getPackagesForTalent(TalentProfile $profile): Collection
    {
        return $this->repository->findActiveByTalentProfileId($profile->id);
    }
}
