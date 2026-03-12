<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HoneypotLog extends Model
{
    protected $fillable = [
        'ip', 'user_agent', 'honeypot_value', 'referer', 'url',
        'country', 'city', 'is_blocked',
    ];

    protected function casts(): array
    {
        return [
            'is_blocked' => 'boolean',
        ];
    }
}
