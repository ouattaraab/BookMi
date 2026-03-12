<?php

namespace App\Http\Middleware;

use App\Services\SecurityEventService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogRateLimitMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() === 429) {
            app(SecurityEventService::class)->log('rate_limit', $request);
        }

        return $response;
    }
}
