<?php

namespace App\Http\Middleware;

use App\Models\PlatformSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $enabled = PlatformSetting::bool('maintenance_enabled');
        } catch (\Throwable) {
            // Table not yet migrated (e.g. during early test bootstrap) → pass through
            return $next($request);
        }

        if (! $enabled) {
            return $next($request);
        }

        // Admins bypass maintenance
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if ($user !== null && $user->is_admin === true) {
            return $next($request);
        }

        // Always-accessible paths
        $exempt = [
            $request->is('maintenance'),
            $request->is('admin') || $request->is('admin/*'),
            $request->is('api/v1/app/version'),
            $request->is('up'),
            $request->is('opcache_reset.php'),
            $request->is('api/v1/auth/*'),
        ];

        if (in_array(true, $exempt, true)) {
            return $next($request);
        }

        $message = PlatformSetting::get('maintenance_message', 'Maintenance en cours.');
        $endAt   = PlatformSetting::get('maintenance_end_at');

        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json([
                'error' => [
                    'code'    => 'MAINTENANCE',
                    'message' => $message,
                    'status'  => 503,
                    'details' => [
                        'maintenance' => true,
                        'end_at'      => $endAt,
                    ],
                ],
            ], 503);
        }

        return redirect()->route('maintenance');
    }
}
