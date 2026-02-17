<?php

namespace App\Policies;

use App\Enums\VerificationStatus;
use App\Models\IdentityVerification;
use App\Models\User;

class IdentityVerificationPolicy
{
    public function view(User $user, IdentityVerification $verification): bool
    {
        return $user->id === $verification->user_id || $user->is_admin;
    }

    public function create(User $user): bool
    {
        return $user->talentProfile !== null
            && ! $user->identityVerifications()
                  ->where('verification_status', VerificationStatus::PENDING)
                  ->exists();
    }
}
