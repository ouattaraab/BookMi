<?php

namespace App\Models;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AdminAlert extends Model
{
    /** @use HasFactory<\Database\Factories\AdminAlertFactory> */
    use HasFactory;

    protected $fillable = [
        'type',
        'severity',
        'subject_type',
        'subject_id',
        'title',
        'description',
        'metadata',
        'status',
        'resolved_at',
        'resolved_by_id',
    ];

    protected function casts(): array
    {
        return [
            'type'        => AlertType::class,
            'severity'    => AlertSeverity::class,
            'metadata'    => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
