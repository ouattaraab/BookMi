<?php

namespace App\Http\Middleware;

use App\Models\PlatformSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Paths that are NEVER blocked by maintenance mode.
     *
     * Note: $request->is() matches against $request->path() which strips the
     * leading slash, so '/' works for the site root, 'admin/*' for all Filament
     * pages, and 'livewire/*' for all Livewire Ajax calls used by Filament.
     */
    private const EXEMPT_PATTERNS = [
        // ── Maintenance page itself ───────────────────────────────────────
        'maintenance',

        // ── Filament admin panel (including login + Livewire Ajax calls) ──
        'admin',
        'admin/*',
        'livewire',
        'livewire/*',

        // ── System / health ───────────────────────────────────────────────
        'up',
        'opcache_reset.php',

        // ── Mobile deep links & service worker ───────────────────────────
        '.well-known/*',
        'firebase-messaging-sw.js',

        // ── Public landing page & marketing ──────────────────────────────
        '/',
        'talents',
        'talents/*',
        'conditions-utilisation',
        'politique-confidentialite',

        // ── Public API (version check, health, auth) ──────────────────────
        'api/v1/app/version',
        'api/v1/health',
        'api/v1/auth/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // If the platform_settings table doesn't exist yet → pass through
        try {
            $enabled = PlatformSetting::bool('maintenance_enabled');
        } catch (\Throwable) {
            return $next($request);
        }

        if (! $enabled) {
            return $next($request);
        }

        // Admins always bypass maintenance (works once logged in via Filament)
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if ($user?->is_admin === true) {
            return $next($request);
        }

        // Always-accessible paths
        if ($request->is(...self::EXEMPT_PATTERNS)) {
            return $next($request);
        }

        // Block: API → JSON 503 | Web → redirect to maintenance page
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
