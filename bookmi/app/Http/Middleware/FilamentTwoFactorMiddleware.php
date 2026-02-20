<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FilamentTwoFactorMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user
            && $user->two_factor_enabled
            && ! $request->session()->get('2fa_passed')
        ) {
            // Avoid redirect loop when already on the challenge page
            if (! $request->routeIs('filament.admin.pages.two-factor-challenge')) {
                return redirect()->route('filament.admin.pages.two-factor-challenge');
            }
        }

        return $next($request);
    }
}
