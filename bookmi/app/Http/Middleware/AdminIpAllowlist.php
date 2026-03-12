<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict admin panel access to a configurable list of IP addresses.
 *
 * Set ADMIN_ALLOWED_IPS=1.2.3.4,5.6.7.8 in .env to enable.
 * Leave ADMIN_ALLOWED_IPS empty to allow all IPs (default — backward compatible).
 */
class AdminIpAllowlist
{
    public function handle(Request $request, Closure $next): Response
    {
        $raw     = config('bookmi.admin_allowed_ips', '');
        $allowed = array_filter(array_map('trim', explode(',', (string) $raw)));

        if (! empty($allowed) && ! in_array($request->ip(), $allowed, true)) {
            abort(403, 'Accès refusé.');
        }

        return $next($request);
    }
}
