<?php

namespace App\Filament\Pages;

use App\Models\PlatformSetting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * @property-read \Filament\Forms\ComponentContainer $form
 */
class PlatformSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Configuration';

    protected static ?string $title = 'Paramètres plateforme';

    protected static ?string $navigationGroup = 'Paramètres';

    protected static ?int $navigationSort = 9;

    protected static string $view = 'filament.pages.platform-settings-page';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return ($user?->is_admin === true) || ($user?->hasRole('admin_ceo') ?? false);
    }

    // ── Form state ────────────────────────────────────────────────────────────

    /** @var array<string, mixed> */
    public array $data = [];

    // ── Read-only config display (non-editable settings) ─────────────────────

    public array $settings = [];

    public function mount(): void
    {
        $levels = config('bookmi.talent.levels', []);

        $this->settings = [];

        foreach ($levels as $key => $cfg) {
            $this->settings['level_' . $key] = [
                'label'       => 'Niveau ' . ucfirst($key),
                'value'       => $cfg['min_bookings'] . ' réservations · note ≥ ' . $cfg['min_rating'],
                'description' => 'Seuils pour atteindre ce niveau (config seulement)',
                'env_key'     => 'talent.levels.' . $key,
            ];
        }

        $this->form->fill([
            'commission_rate'           => (float) (PlatformSetting::get('commission_rate') ?? config('bookmi.commission_rate', 15)),
            'escrow_auto_confirm_hours' => (int) (PlatformSetting::get('escrow_auto_confirm_hours') ?? config('bookmi.escrow.auto_confirm_hours', 48)),
            'escrow_payout_delay_hours' => (int) (PlatformSetting::get('escrow_payout_delay_hours') ?? config('bookmi.escrow.payout_delay_hours', 24)),
            'low_rating_alert'          => (float) (PlatformSetting::get('low_rating_alert') ?? config('bookmi.talent.low_rating_threshold', 3.0)),
            'maintenance_mode'          => PlatformSetting::bool('maintenance_mode', false),
            'force_update_version'      => PlatformSetting::get('force_update_version') ?? '',
            'force_update_message'      => PlatformSetting::get('force_update_message') ?? '',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Paramètres financiers')
                    ->description('Ces valeurs sont stockées en base de données et écrasent la configuration par défaut.')
                    ->schema([
                        Forms\Components\TextInput::make('commission_rate')
                            ->label('Taux de commission (%)')
                            ->helperText('Pourcentage prélevé sur chaque réservation payée.')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->required()
                            ->suffix('%'),

                        Forms\Components\TextInput::make('escrow_auto_confirm_hours')
                            ->label('Délai auto-confirmation escrow (heures)')
                            ->helperText('Durée avant confirmation automatique du paiement escrow.')
                            ->integer()
                            ->minValue(1)
                            ->required()
                            ->suffix('h'),

                        Forms\Components\TextInput::make('escrow_payout_delay_hours')
                            ->label('Délai versement talent (heures)')
                            ->helperText('Délai après prestation avant reversement du cachet au talent.')
                            ->integer()
                            ->minValue(0)
                            ->required()
                            ->suffix('h'),

                        Forms\Components\TextInput::make('low_rating_alert')
                            ->label('Seuil alerte qualité (note / 5)')
                            ->helperText('Note moyenne en dessous de laquelle une alerte est générée.')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(5)
                            ->required()
                            ->suffix('/ 5'),
                    ])->columns(2),

                Forms\Components\Section::make('Application mobile')
                    ->description('Paramètres de maintenance et de mise à jour forcée.')
                    ->schema([
                        Forms\Components\Toggle::make('maintenance_mode')
                            ->label('Mode maintenance')
                            ->helperText('Active le mode maintenance — les utilisateurs voient une page de maintenance.')
                            ->onColor('danger')
                            ->offColor('success'),

                        Forms\Components\TextInput::make('force_update_version')
                            ->label('Version minimale requise')
                            ->helperText('Version semver (ex: 1.2.0) en dessous de laquelle la mise à jour est forcée. Laisser vide pour désactiver.')
                            ->placeholder('ex: 1.2.0')
                            ->maxLength(20),

                        Forms\Components\Textarea::make('force_update_message')
                            ->label('Message de mise à jour forcée')
                            ->helperText('Message affiché à l\'utilisateur lorsqu\'une mise à jour est obligatoire.')
                            ->rows(3)
                            ->maxLength(500),
                    ])->columns(1),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        PlatformSetting::set('commission_rate', (float) $data['commission_rate'], 'float');
        PlatformSetting::set('escrow_auto_confirm_hours', (int) $data['escrow_auto_confirm_hours'], 'integer');
        PlatformSetting::set('escrow_payout_delay_hours', (int) $data['escrow_payout_delay_hours'], 'integer');
        PlatformSetting::set('low_rating_alert', (float) $data['low_rating_alert'], 'float');
        PlatformSetting::set('maintenance_mode', (bool) ($data['maintenance_mode'] ?? false), 'boolean');
        PlatformSetting::set('force_update_version', (string) ($data['force_update_version'] ?? ''), 'string');
        PlatformSetting::set('force_update_message', (string) ($data['force_update_message'] ?? ''), 'string');

        Notification::make()
            ->title('Paramètres mis à jour')
            ->success()
            ->send();
    }
}
