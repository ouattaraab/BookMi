<?php

namespace App\Filament\Pages;

use App\Models\PlatformSetting;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class MaintenancePage extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Mode maintenance';
    protected static ?string $title           = 'Mode maintenance';
    protected static ?string $navigationGroup = 'Paramètres';
    protected static ?int $navigationSort     = 10;
    protected static string $view             = 'filament.pages.maintenance-page';

    public bool   $enabled  = false;
    public string $message  = '';
    public string $end_at   = '';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return ($user?->is_admin === true) || ($user?->hasRole('admin_ceo') ?? false);
    }

    public static function getNavigationBadge(): ?string
    {
        return PlatformSetting::bool('maintenance_enabled') ? 'ACTIF' : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return PlatformSetting::bool('maintenance_enabled') ? 'danger' : null;
    }

    public function mount(): void
    {
        $this->enabled = PlatformSetting::bool('maintenance_enabled');
        $this->message = (string) PlatformSetting::get('maintenance_message', '');
        $this->end_at  = (string) PlatformSetting::get('maintenance_end_at', '');
    }

    public function save(): void
    {
        PlatformSetting::set('maintenance_enabled', $this->enabled ? 'true' : 'false', 'bool');
        PlatformSetting::set('maintenance_message', $this->message);
        PlatformSetting::set('maintenance_end_at', $this->end_at !== '' ? $this->end_at : null);

        Notification::make()
            ->title('Paramètres enregistrés')
            ->success()
            ->send();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Mode maintenance';
    }
}
