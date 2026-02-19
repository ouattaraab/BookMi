<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsManager
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->hasRole('manager', 'api')) {
            abort(403, 'Accès réservé aux managers.');
        }

        return $next($request);
    }
}
