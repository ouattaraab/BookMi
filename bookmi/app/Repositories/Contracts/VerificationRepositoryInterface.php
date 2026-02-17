<?php

namespace App\Repositories\Contracts;

use App\Models\IdentityVerification;
use Illuminate\Database\Eloquent\Collection;

interface VerificationRepositoryInterface
{
    public function find(int $id): ?IdentityVerification;

    public function findByUserId(int $userId): ?IdentityVerification;

    public function findPendingByUserId(int $userId, bool $lockForUpdate = false): ?IdentityVerification;

    /**
     * @return Collection<int, IdentityVerification>
     */
    public function findPending(): Collection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): IdentityVerification;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(IdentityVerification $verification, array $data): IdentityVerification;
}
