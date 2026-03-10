<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds essential HTTP security headers to every response.
 *
 * [H1] Covers: X-Content-Type-Options, X-Frame-Options, HSTS, Referrer-Policy,
 *              X-XSS-Protection, Content-Security-Policy.
 *
 * CSP strategy:
 * - API routes (JSON): no CSP (not rendered by browsers as HTML).
 * - Web routes: per-request nonce replaces 'unsafe-inline' for script-src.
 *   The nonce is exposed via app('csp_nonce') and the @nonce Blade directive.
 *   'unsafe-eval' is retained for Alpine.js / Filament compatibility.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Generate a cryptographically random nonce for this request.
        $nonce = base64_encode(random_bytes(16));
        app()->instance('csp_nonce', $nonce);

        $response = $next($request);

        // Prevent MIME sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Legacy XSS filter (for older browsers)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Control referrer information
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Force HTTPS in production
        if (app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Skip CSP for API routes — JSON responses are not rendered as HTML.
        if ($request->is('api/*')) {
            return $response;
        }

        // Content-Security-Policy with per-request nonce.
        // 'unsafe-eval' is kept for Alpine.js / Filament / Livewire compatibility.
        // 'unsafe-inline' removed from script-src — replaced by nonce.
        // Inline styles still require 'unsafe-inline' for Filament/Livewire.
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval' https://cdn.jsdelivr.net https://js.paystack.co",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: https: blob:",
            "connect-src 'self' https://api.paystack.co https://fcm.googleapis.com",
            "frame-src 'self' https://js.paystack.co",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
