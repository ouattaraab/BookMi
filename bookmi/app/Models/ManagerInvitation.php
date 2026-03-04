<?php

namespace App\Models;

use App\Enums\ManagerInvitationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int                         $id
 * @property int                         $talent_profile_id
 * @property string                      $manager_email
 * @property int|null                    $manager_id
 * @property ManagerInvitationStatus     $status
 * @property string|null                 $manager_comment
 * @property string                      $token
 * @property \Carbon\Carbon              $invited_at
 * @property \Carbon\Carbon|null         $responded_at
 * @property-read TalentProfile|null     $talentProfile
 * @property-read User|null              $manager
 */
class ManagerInvitation extends Model
{
    protected $fillable = [
        'talent_profile_id',
        'manager_email',
        'manager_id',
        'status',
        'manager_comment',
        'token',
        'invited_at',
        'responded_at',
    ];

    protected $casts = [
        'status'       => ManagerInvitationStatus::class,
        'invited_at'   => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function talentProfile(): BelongsTo
    {
        return $this->belongsTo(TalentProfile::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /** @param Builder<ManagerInvitation> $query */
    public function scopePending(Builder $query): void
    {
        $query->where('status', ManagerInvitationStatus::Pending->value);
    }

    /**
     * @param Builder<ManagerInvitation> $query
     */
    public function scopeForManager(Builder $query, string $email): void
    {
        $query->where('manager_email', strtolower($email));
    }
}
