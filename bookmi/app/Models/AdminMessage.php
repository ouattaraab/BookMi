<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminMessage extends Model
{
    protected $fillable = [
        'admin_id',
        'type',
        'target_type',
        'target_user_id',
        'title',
        'body',
        'recipients_count',
    ];

    protected function casts(): array
    {
        return [
            'type'        => 'string',
            'target_type' => 'string',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
