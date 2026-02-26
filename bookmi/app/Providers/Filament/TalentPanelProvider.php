<?php

namespace App\Providers\Filament;

use App\Filament\Talent\Pages\PayoutMethodPage;
use App\Filament\Talent\Pages\WithdrawalRequestTalentPage;
use Filament\FontProviders\GoogleFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class TalentPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('talent')
            ->path('talent-portal')
            ->login()

            ->colors([
                'primary' => [
                    50  => '227, 242, 253',
                    100 => '187, 222, 251',
                    200 => '144, 202, 249',
                    300 => '100, 181, 246',
                    400 => '66, 165, 245',
                    500 => '33, 150, 243',
                    600 => '30, 136, 229',
                    700 => '25, 118, 210',
                    800 => '21, 101, 192',
                    900 => '13, 71, 161',
                    950 => '8, 46, 100',
                ],
                'danger'  => Color::Red,
                'warning' => Color::Amber,
                'success' => Color::Green,
            ])

            ->font('Nunito', provider: GoogleFontProvider::class)
            ->brandName('BookMi — Espace Talent')

            ->pages([
                PayoutMethodPage::class,
                WithdrawalRequestTalentPage::class,
            ])

            ->authGuard('web')

            // Seuls les utilisateurs avec le rôle 'talent' peuvent accéder
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\EnsureTalentRole::class,
            ])

            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ]);
    }
}
