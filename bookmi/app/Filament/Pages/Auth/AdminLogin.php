<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Pages\Auth\Login;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Custom admin login page.
 *
 * Adds:
 *  - Per-IP rate-limiting: max 5 attempts / 15 minutes (layered on top of Filament's own limiter)
 *  - Honeypot field: bots that fill the hidden "website" field are silently blocked
 */
class AdminLogin extends Login
{
    private function rateLimitKey(): string
    {
        return 'admin_login:' . request()->ip();
    }

    /**
     * Override authenticate to add rate-limiting and honeypot checks.
     */
    public function authenticate(): ?LoginResponse
    {
        $key = $this->rateLimitKey();

        // 1. Honeypot — bots fill hidden "website" field; humans leave it empty
        $data = $this->form->getRawState();
        if (! empty($data['website'] ?? '')) {
            // Silently fail — do not reveal the honeypot
            $this->redirect(request()->url());

            return null;
        }

        // 2. Rate limit check — block after 5 failures per 15 minutes
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $this->addError(
                'data.email',
                "Trop de tentatives de connexion. Réessayez dans {$seconds} secondes."
            );

            return null;
        }

        try {
            $response = parent::authenticate();
            // Success — clear the rate limiter
            RateLimiter::clear($key);

            return $response;
        } catch (ValidationException $e) {
            // Failure — count the attempt (900s = 15min window)
            RateLimiter::hit($key, 900);
            throw $e;
        }
    }

    /**
     * Inject honeypot field into the form schema (hidden via CSS).
     * Override getFormSchema to add the honeypot.
     *
     * @return array<Component>
     */
    protected function getFormSchema(): array
    {
        $schema = parent::getFormSchema();

        // Append a hidden honeypot field — bots fill it, humans don't
        $schema[] = TextInput::make('website')
            ->label('Website')
            ->extraAttributes([
                'style'        => 'position:absolute;left:-9999px;top:-9999px;opacity:0;height:0;width:0;',
                'tabindex'     => '-1',
                'autocomplete' => 'off',
                'aria-hidden'  => 'true',
            ])
            ->dehydrated(false);

        return $schema;
    }
}
