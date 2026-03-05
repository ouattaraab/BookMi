<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCguVersion
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $currentVersion  = config('bookmi.consent.cgu_version');
        $acceptedVersion = $user->cgu_version_accepted;

        if ($acceptedVersion === null || version_compare($acceptedVersion, $currentVersion, '<')) {
            return response()->json([
                'message'           => 'Une nouvelle version des CGU doit être acceptée.',
                'requires_reconsent' => true,
                'cgu_version'       => $currentVersion,
            ], 403);
        }

        return $next($request);
    }
}
