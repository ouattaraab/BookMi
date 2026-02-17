<?php

namespace App\Repositories\Eloquent;

use App\Models\IdentityVerification;
use App\Repositories\Contracts\VerificationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class VerificationRepository implements VerificationRepositoryInterface
{
    public function find(int $id): ?IdentityVerification
    {
        return IdentityVerification::find($id);
    }

    public function findByUserId(int $userId): ?IdentityVerification
    {
        return IdentityVerification::where('user_id', $userId)->latest()->first();
    }

    public function findPendingByUserId(int $userId, bool $lockForUpdate = false): ?IdentityVerification
    {
        $query = IdentityVerification::where('user_id', $userId)->pending();

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    /**
     * @return Collection<int, IdentityVerification>
     */
    public function findPending(): Collection
    {
        return IdentityVerification::pending()->with('user')->latest()->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): IdentityVerification
    {
        return IdentityVerification::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(IdentityVerification $verification, array $data): IdentityVerification
    {
        $verification->update($data);

        return $verification->refresh();
    }
}
