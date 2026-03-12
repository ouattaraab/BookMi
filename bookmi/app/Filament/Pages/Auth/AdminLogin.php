<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Pages\Auth\Login;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

/**
 * Custom admin login page.
 *
 * Adds:
 *  - Per-IP rate-limiting: max 5 attempts / 15 minutes
 *  - Honeypot field: bots that fill the hidden "website" field are silently blocked
 *  - Google reCAPTCHA v2 (when NOCAPTCHA_SITEKEY is configured)
 */
class AdminLogin extends Login
{
    private function rateLimitKey(): string
    {
        return 'admin_login:' . request()->ip();
    }

    /**
     * Override authenticate to add rate-limiting, honeypot, and reCAPTCHA checks.
     */
    public function authenticate(): ?LoginResponse
    {
        $key = $this->rateLimitKey();

        // 1. Honeypot — bots fill hidden "website" field; humans leave it empty
        $data = $this->form->getRawState();
        if (! empty($data['website'] ?? '')) {
            $this->redirect(request()->url());

            return null;
        }

        // 2. reCAPTCHA validation (only when configured in .env)
        if (config('captcha.sitekey') && config('captcha.secret')) {
            $token = request()->input('g-recaptcha-response', '');
            if (! app('captcha')->verifyResponse($token, request()->ip())) {
                $this->addError('data.email', 'La vérification CAPTCHA a échoué. Veuillez recommencer.');

                return null;
            }
        }

        // 3. Rate limit check — block after 5 failures per 15 minutes
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
            RateLimiter::clear($key);

            return $response;
        } catch (ValidationException $e) {
            RateLimiter::hit($key, 900);
            throw $e;
        }
    }

    /**
     * @return array<Component>
     */
    protected function getFormSchema(): array
    {
        $schema = parent::getFormSchema();

        // Honeypot — hidden from humans, filled by bots
        $schema[] = TextInput::make('website')
            ->label('Website')
            ->extraAttributes([
                'style'        => 'position:absolute;left:-9999px;top:-9999px;opacity:0;height:0;width:0;',
                'tabindex'     => '-1',
                'autocomplete' => 'off',
                'aria-hidden'  => 'true',
            ])
            ->dehydrated(false);

        // reCAPTCHA widget (only rendered when NOCAPTCHA_SITEKEY is configured)
        if (config('captcha.sitekey') && config('captcha.secret')) {
            $siteKey = config('captcha.sitekey');
            $schema[] = Placeholder::make('recaptcha_widget')
                ->label('')
                ->content(new HtmlString(
                    '<div class="g-recaptcha" data-sitekey="' . e($siteKey) . '" style="transform:scale(0.9);transform-origin:0 0;"></div>' .
                    '<script src="https://www.google.com/recaptcha/api.js" async defer></script>'
                ));
        }

        return $schema;
    }
}
