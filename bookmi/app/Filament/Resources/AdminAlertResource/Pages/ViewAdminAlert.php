<?php

namespace App\Filament\Resources\AdminAlertResource\Pages;

use App\Filament\Resources\AdminAlertResource;
use App\Services\ActivityLogger;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewAdminAlert extends ViewRecord
{
    protected static string $resource = AdminAlertResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('resolve')
                ->label('Résoudre')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn (): bool => $this->record->status === 'open')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->update([
                        'status'         => 'resolved',
                        'resolved_at'    => now(),
                        'resolved_by_id' => Auth::id(),
                    ]);

                    ActivityLogger::log('alert.resolved', $this->record, [
                        'title'    => $this->record->title,
                        'severity' => is_string($this->record->severity) ? $this->record->severity : $this->record->severity?->value,
                    ]);

                    Notification::make()->title('Alerte résolue')->success()->send();
                    $this->record->refresh();
                }),

            Actions\Action::make('dismiss')
                ->label('Ignorer')
                ->icon('heroicon-o-archive-box')
                ->color('gray')
                ->visible(fn (): bool => $this->record->status === 'open')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->update(['status' => 'dismissed']);

                    ActivityLogger::log('alert.dismissed', $this->record, [
                        'title' => $this->record->title,
                    ]);

                    Notification::make()->title('Alerte ignorée')->send();
                    $this->record->refresh();
                }),
        ];
    }
}
