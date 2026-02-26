<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTalentRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->hasRole('talent')) {
            abort(403, 'Accès réservé aux talents BookMi.');
        }

        return $next($request);
    }
}
