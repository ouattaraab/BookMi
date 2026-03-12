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
 * Protections actives :
 *  - Per-IP rate-limiting : max 5 tentatives / 15 minutes
 *  - Honeypot : le champ "website" invisible bloque les bots qui le remplissent
 */
class AdminLogin extends Login
{
    private function rateLimitKey(): string
    {
        return 'admin_login:' . request()->ip();
    }

    public function authenticate(): ?LoginResponse
    {
        $key = $this->rateLimitKey();

        // 1. Honeypot — bots fill hidden "website" field; humans leave it empty
        $data = $this->form->getRawState();
        if (! empty($data['website'] ?? '')) {
            $this->logHoneypotHit($data['website'] ?? '');
            $this->redirect(request()->url());

            return null;
        }

        // 2. Rate limit — max 5 failures per 15 minutes
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

    private function logHoneypotHit(string $honeypotValue): void
    {
        $request = request();
        $geo = app(\App\Services\GeoIpService::class)->lookup($request->ip() ?? '');

        \App\Models\HoneypotLog::create([
            'ip'             => $request->ip(),
            'user_agent'     => $request->userAgent(),
            'honeypot_value' => $honeypotValue,
            'referer'        => $request->header('referer'),
            'url'            => $request->fullUrl(),
            'country'        => $geo['country'],
            'city'           => $geo['city'],
        ]);
    }

    /**
     * @return array<Component>
     */
    protected function getFormSchema(): array
    {
        $schema = parent::getFormSchema();

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
