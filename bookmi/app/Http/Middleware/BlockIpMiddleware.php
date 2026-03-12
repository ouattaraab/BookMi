<?php

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class BlockIpMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        $isBlocked = Cache::remember("blocked_ip:{$ip}", now()->addMinutes(10), function () use ($ip) {
            return BlockedIp::where('ip', $ip)->exists();
        });

        if ($isBlocked) {
            abort(403, 'Votre adresse IP a été bloquée.');
        }

        return $next($request);
    }
}
