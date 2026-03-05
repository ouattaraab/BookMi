<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property \Carbon\Carbon $locked_at
 * @property \Carbon\Carbon $locked_until
 * @property \Carbon\Carbon|null $unlocked_at
 */
class LoginLockoutLog extends Model
{
    protected $fillable = [
        'email',
        'user_id',
        'client_type',
        'ip_address',
        'user_agent',
        'attempts_count',
        'locked_at',
        'locked_until',
        'unlocked_at',
        'unlocked_by',
    ];

    protected function casts(): array
    {
        return [
            'locked_at'    => 'datetime',
            'locked_until' => 'datetime',
            'unlocked_at'  => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function unlockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unlocked_by');
    }

    /**
     * Returns true when the lockout is still in effect (not yet expired and not manually unlocked).
     */
    public function isActive(): bool
    {
        return $this->unlocked_at === null
            && $this->locked_until->isFuture();
    }
}
