<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (! auth()->check() || ! auth()->user()->hasRole($role, 'api')) {
            abort(403, 'Accès non autorisé.');
        }

        return $next($request);
    }
}
