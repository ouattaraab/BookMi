<?php

namespace App\Policies;

use App\Models\TalentProfile;
use App\Models\User;

class TalentProfilePolicy
{
    public function view(User $user, TalentProfile $profile): bool
    {
        return true;
    }

    public function update(User $user, TalentProfile $profile): bool
    {
        return $user->id === $profile->user_id;
    }

    public function delete(User $user, TalentProfile $profile): bool
    {
        return $user->id === $profile->user_id;
    }
}
