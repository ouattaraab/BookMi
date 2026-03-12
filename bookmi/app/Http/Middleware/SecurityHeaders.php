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

        // Belt-and-suspenders: remove PHP version disclosure header.
        // Primary removal is handled by php_flag expose_php Off in .htaccess,
        // but some hosting stacks re-add it after PHP processing.
        $response->headers->remove('X-Powered-By');

        // Prevent MIME sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Prevent clickjacking — overridden to 'none' for non-framed pages below
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Legacy XSS filter (for older browsers)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Control referrer information
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Force HTTPS in production — with preload flag for HSTS preload list submission
        if (app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Permissions-Policy: restrict access to sensitive browser APIs
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(self), geolocation=(self), payment=(self)');

        // Skip HTML-specific headers for API routes — JSON is not rendered as HTML.
        if ($request->is('api/*')) {
            return $response;
        }

        // For web routes: no framing allowed outside Filament (which uses iframes for previews)
        if (! $request->is('admin/*') && ! $request->is('filament/*')) {
            $response->headers->set('X-Frame-Options', 'DENY');
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
            "upgrade-insecure-requests",
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
