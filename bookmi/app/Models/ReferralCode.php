<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ReferralCode extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'uses_count',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Referral, $this>
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class, 'code_used', 'code');
    }

    /**
     * Generate a unique referral code for a user.
     * Format: first 4 letters of first name + 4 random alphanumeric chars (uppercase).
     */
    public static function generateFor(User $user): self
    {
        $base = strtoupper(substr(preg_replace('/[^a-z]/i', '', $user->first_name), 0, 4));
        $base = str_pad($base, 4, 'X');

        do {
            $suffix = strtoupper(Str::random(4));
            $code = $base . $suffix;
        } while (static::where('code', $code)->exists());

        return static::create([
            'user_id' => $user->id,
            'code' => $code,
        ]);
    }
}
