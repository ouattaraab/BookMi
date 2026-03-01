<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TalentFollow extends Model
{
    /** No updated_at — pivot-like table. */
    public $timestamps = false;

    public const CREATED_AT = 'created_at';

    protected $fillable = ['user_id', 'talent_profile_id'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<TalentProfile, $this> */
    public function talentProfile(): BelongsTo
    {
        return $this->belongsTo(TalentProfile::class);
    }
}
