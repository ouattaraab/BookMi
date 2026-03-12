<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class FilamentTwoFactorMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $adminNeedsTwoFactor = $user && $user->is_admin;
        $userHasOptIn2FA     = $user && $user->two_factor_enabled;

        if (
            ($adminNeedsTwoFactor || $userHasOptIn2FA)
            && ! $request->session()->get('2fa_passed')
        ) {
            // Avoid redirect loop when already on the challenge page
            if ($request->routeIs('filament.admin.pages.two-factor-challenge')) {
                return $next($request);
            }

            try {
                if ($adminNeedsTwoFactor && ! $user->two_factor_enabled) {
                    // Admin without 2FA configured — force setup before continuing
                    return redirect()->route('filament.admin.pages.two-factor-challenge')
                        ->with('2fa_setup_required', true);
                }

                return redirect()->route('filament.admin.pages.two-factor-challenge');
            } catch (RouteNotFoundException $e) {
                // Route not registered yet (e.g. during tests before Filament panel boots).
                // Fall through and let the request proceed — Filament will handle auth itself.
                return $next($request);
            }
        }

        return $next($request);
    }
}
