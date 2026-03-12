<?php

namespace App\Services;

use App\Models\BlockedIp;
use App\Models\SecurityEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SecurityEventService
{
    public function __construct(private readonly GeoIpService $geo)
    {
    }

    public function log(
        string $type,
        Request $request,
        array $extra = []
    ): void {
        $ip = $request->ip() ?? '';

        // Deduplicate rate_limit and suspicious_404: max 1 log per IP per minute
        if (in_array($type, ['rate_limit', 'suspicious_404'], true)) {
            $dedupeKey = "sec_event_dedup:{$type}:{$ip}";
            if (Cache::has($dedupeKey)) {
                return;
            }
            Cache::put($dedupeKey, 1, now()->addMinute());
        }

        $geo   = $this->geo->lookup($ip);
        $isBlocked = BlockedIp::where('ip', $ip)->exists();

        SecurityEvent::create([
            'type'       => $type,
            'severity'   => SecurityEvent::severityForType($type),
            'ip'         => $ip,
            'user_agent' => $request->userAgent(),
            'method'     => $request->method(),
            'url'        => $request->fullUrl(),
            'referer'    => $request->header('referer'),
            'country'    => $geo['country'],
            'city'       => $geo['city'],
            'email'      => $extra['email'] ?? null,
            'metadata'   => $extra['metadata'] ?? null,
            'ip_blocked' => $isBlocked,
        ]);
    }
}
