<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class PlatformSettingsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Configuration';

    protected static ?string $title = 'Paramètres plateforme';

    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.pages.platform-settings-page';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return ($user?->is_admin === true) || ($user?->hasRole('admin_ceo') ?? false);
    }

    public array $settings = [];

    public function mount(): void
    {
        $levels = config('bookmi.talent.levels', []);

        $this->settings = [
            'commission' => [
                'label'       => 'Taux de commission',
                'value'       => config('bookmi.commission_rate', 15) . '%',
                'description' => 'Prélevé sur chaque prestation',
                'env_key'     => 'commission_rate',
            ],
            'escrow_confirm' => [
                'label'       => 'Délai auto-confirmation escrow',
                'value'       => config('bookmi.escrow.auto_confirm_hours', 48) . 'h',
                'description' => 'Avant confirmation automatique du paiement',
                'env_key'     => 'escrow.auto_confirm_hours',
            ],
            'escrow_payout' => [
                'label'       => 'Délai versement talent',
                'value'       => config('bookmi.escrow.payout_delay_hours', 24) . 'h',
                'description' => 'Délai avant reversement du cachet',
                'env_key'     => 'escrow.payout_delay_hours',
            ],
            'low_rating_alert' => [
                'label'       => 'Seuil alerte qualité',
                'value'       => config('bookmi.talent.low_rating_threshold', 3.0) . '/5',
                'description' => 'Note moyenne en dessous de laquelle une alerte est générée',
                'env_key'     => 'talent.low_rating_threshold',
            ],
        ];

        foreach ($levels as $key => $cfg) {
            $this->settings['level_' . $key] = [
                'label'       => 'Niveau ' . ucfirst($key),
                'value'       => $cfg['min_bookings'] . ' réservations · note ≥ ' . $cfg['min_rating'],
                'description' => 'Seuils pour atteindre ce niveau',
                'env_key'     => 'talent.levels.' . $key,
            ];
        }
    }
}
