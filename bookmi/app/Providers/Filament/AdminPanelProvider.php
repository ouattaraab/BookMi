<?php

namespace App\Providers\Filament;

use Filament\FontProviders\GoogleFontProvider;
use App\Http\Middleware\FilamentTwoFactorMiddleware;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()

            // ── Charte BookMi — Brand Blue #2196F3 (palette Material Design) ──
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
                'info'    => Color::Sky,
                'gray'    => Color::Slate,
            ])

            // ── Typographie — Nunito (police officielle BookMi) ──
            ->font('Nunito', provider: GoogleFontProvider::class)

            // ── Logo textuel bicolore "Book" blanc + "Mi" bleu ──
            ->brandName('BookMi')
            ->brandLogo(fn () => view('filament.logo'))
            ->brandLogoHeight('2rem')

            // ── CSS custom BookMi (sidebar navy, glassmorphism, responsive) ──
            ->renderHook(
                'panels::head.end',
                fn () => view('filament.custom-styles'),
            )

            // ── Firebase FCM Web Push (token registration + foreground handler) ──
            ->renderHook(
                'panels::head.end',
                fn () => view('filament.firebase-scripts'),
            )

            // ── Cloche de notifications en haut à droite de la topbar ──
            ->renderHook(
                'panels::topbar.end',
                fn () => Blade::render("@livewire('shared.notification-bell')"),
            )

            // ── Footer sidebar : utilisateur connecté + déconnexion ──
            ->renderHook(
                'panels::sidebar.footer',
                fn () => view('filament.sidebar-footer'),
            )

            // ── Layout — plein écran, sidebar collapsible ──
            ->maxContentWidth(MaxWidth::Full)
            ->sidebarCollapsibleOnDesktop()

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // AccountWidget retiré du dashboard — déplacé dans le footer sidebar
                \App\Filament\Widgets\BookingsOverviewWidget::class,
                \App\Filament\Widgets\RevenueWidget::class,
                \App\Filament\Widgets\AlertsWidget::class,
                \App\Filament\Widgets\TalentsLevelWidget::class,
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
            ])
            ->authMiddleware([
                Authenticate::class,
                FilamentTwoFactorMiddleware::class,
            ]);
    }
}
