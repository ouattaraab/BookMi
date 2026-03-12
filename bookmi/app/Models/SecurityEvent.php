<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityEvent extends Model
{
    protected $fillable = [
        'type', 'severity', 'ip', 'user_agent', 'method',
        'url', 'referer', 'country', 'city', 'email',
        'metadata', 'ip_blocked',
    ];

    protected function casts(): array
    {
        return [
            'metadata'   => 'array',
            'ip_blocked' => 'boolean',
        ];
    }

    public static function severityForType(string $type): string
    {
        return match ($type) {
            'login_locked'     => 'high',
            'blocked_attempt'  => 'high',
            'honeypot_hit'     => 'medium',
            'login_failed'     => 'medium',
            'rate_limit'       => 'medium',
            'suspicious_404'   => 'low',
            default            => 'medium',
        };
    }
}
