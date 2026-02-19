<?php

namespace App\Models;

use App\Enums\WarningStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminWarning extends Model
{
    /** @use HasFactory<\Database\Factories\AdminWarningFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'issued_by_id',
        'reason',
        'details',
        'status',
        'resolved_at',
        'resolved_by_id',
    ];

    protected function casts(): array
    {
        return [
            'status'      => WarningStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_id');
    }
}
