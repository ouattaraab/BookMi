<?php

namespace App\Models;

use App\Enums\ConsentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserConsent extends Model
{
    protected $fillable = [
        'user_id',
        'consent_type',
        'status',
        'ip_address',
        'user_agent',
        'device_id',
        'document_version',
        'consented_at',
        'withdrawn_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status'       => 'boolean',
            'consented_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'metadata'     => 'array',
            'consent_type' => ConsentType::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
