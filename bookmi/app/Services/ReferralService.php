<?php

namespace App\Services;

use App\Jobs\SendPushNotification;
use App\Models\Referral;
use App\Models\ReferralCode;
use App\Models\User;

class ReferralService
{
    /**
     * Ensure the user has a referral code, generating one if missing.
     */
    public function ensureCodeFor(User $user): ReferralCode
    {
        return $user->referralCode ?? ReferralCode::generateFor($user);
    }

    /**
     * Apply a referral code after a new user registers.
     *
     * - Finds the referrer by their code.
     * - Creates a `pending` Referral row.
     * - Stores the code on the new user.
     * - Does nothing if the code is invalid or already used.
     */
    public function applyCode(User $newUser, string $code): void
    {
        $referrerCode = ReferralCode::where('code', strtoupper(trim($code)))->first();

        if (! $referrerCode) {
            return;
        }

        // Prevent self-referral
        if ($referrerCode->user_id === $newUser->id) {
            return;
        }

        // Idempotent — one referral per referred user
        if (Referral::where('referred_user_id', $newUser->id)->exists()) {
            return;
        }

        Referral::create([
            'referrer_id'      => $referrerCode->user_id,
            'referred_user_id' => $newUser->id,
            'code_used'        => $referrerCode->code,
            'status'           => 'pending',
        ]);

        $newUser->update(['referred_by_code' => $referrerCode->code]);
        $referrerCode->increment('uses_count');

        // Notify referrer
        SendPushNotification::dispatch(
            $referrerCode->user_id,
            'Nouveau filleul 🎉',
            "{$newUser->first_name} vient de s'inscrire avec votre code de parrainage.",
            ['type' => 'referral_joined'],
        );
    }

    /**
     * Mark a referral as completed (e.g. when referred user completes their first booking).
     */
    public function markCompleted(User $referredUser): void
    {
        Referral::where('referred_user_id', $referredUser->id)
            ->where('status', 'pending')
            ->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);
    }

    /**
     * Get referral statistics for a user.
     *
     * @return array{code: string, total: int, completed: int, pending: int}
     */
    public function getStats(User $user): array
    {
        $referralCode = $this->ensureCodeFor($user);

        $referrals = Referral::where('referrer_id', $user->id);

        return [
            'code'      => $referralCode->code,
            'total'     => (clone $referrals)->count(),
            'completed' => (clone $referrals)->where('status', 'completed')->count(),
            'pending'   => (clone $referrals)->where('status', 'pending')->count(),
        ];
    }
}
