<?php

namespace App\Policies;

use App\Models\ServicePackage;
use App\Models\User;

class ServicePackagePolicy
{
    public function update(User $user, ServicePackage $package): bool
    {
        return $user->id === $package->talentProfile->user_id;
    }

    public function delete(User $user, ServicePackage $package): bool
    {
        return $user->id === $package->talentProfile->user_id;
    }
}
