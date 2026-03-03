<?php

namespace App\Filament\Pages;

use App\Models\PlatformSetting;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class AppVersionPage extends Page
{
    protected static ?string $slug                  = 'app-version';
    protected static ?string $navigationIcon        = 'heroicon-o-device-phone-mobile';
    protected static ?string $navigationLabel       = 'Version app mobile';
    protected static ?string $title                 = 'Version app mobile';
    protected static ?string $navigationGroup       = 'Paramètres';
    protected static ?int    $navigationSort        = 11;
    protected static bool    $shouldRegisterNavigation = false;
    protected static string $view             = 'filament.pages.app-version-page';

    public string $version_required  = '1.0.0';
    public string $update_type       = 'none';
    public string $update_message    = '';
    public string $features          = '';
    public string $play_store_url    = '';
    public string $app_store_url     = '';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return ($user?->is_admin === true) || ($user?->hasRole('admin_ceo') ?? false);
    }

    public function mount(): void
    {
        $this->version_required = (string) PlatformSetting::get('app_version_required', '1.0.0');
        $this->update_type      = (string) PlatformSetting::get('app_update_type', 'none');
        $this->update_message   = (string) PlatformSetting::get('app_update_message', '');
        $this->play_store_url   = (string) PlatformSetting::get('play_store_url', '');
        $this->app_store_url    = (string) PlatformSetting::get('app_store_url', '');

        $featuresRaw    = PlatformSetting::get('app_update_features', '[]');
        $featuresArray  = json_decode((string) $featuresRaw, true) ?? [];
        $this->features = implode("\n", $featuresArray);
    }

    public function save(): void
    {
        $this->validate([
            'version_required' => ['required', 'regex:/^\d+\.\d+\.\d+$/'],
            'update_type'      => ['required', 'in:none,optional,forced'],
            'play_store_url'   => ['nullable', 'url'],
            'app_store_url'    => ['nullable', 'url'],
        ]);

        // Features: one per line → JSON array
        $featuresArray = array_values(array_filter(
            array_map('trim', explode("\n", $this->features)),
            fn (string $l) => $l !== '',
        ));

        PlatformSetting::set('app_version_required', $this->version_required);
        PlatformSetting::set('app_update_type', $this->update_type);
        PlatformSetting::set('app_update_message', $this->update_message);
        PlatformSetting::set('app_update_features', json_encode($featuresArray), 'json');
        PlatformSetting::set('play_store_url', $this->play_store_url);
        PlatformSetting::set('app_store_url', $this->app_store_url);

        Notification::make()
            ->title('Configuration enregistrée')
            ->success()
            ->send();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Version app mobile';
    }
}
