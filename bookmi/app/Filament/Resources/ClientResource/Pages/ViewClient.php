<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewClient extends ViewRecord
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('toggle_suspend')
                ->label(fn (): string => $this->record->is_suspended ? 'Réactiver' : 'Suspendre')
                ->icon(fn (): string => $this->record->is_suspended ? 'heroicon-o-check-circle' : 'heroicon-o-no-symbol')
                ->color(fn (): string => $this->record->is_suspended ? 'success' : 'danger')
                ->requiresConfirmation()
                ->form(fn (): array => $this->record->is_suspended ? [] : [
                    Forms\Components\Textarea::make('suspension_reason')
                        ->label('Motif de suspension')
                        ->required()
                        ->maxLength(500),
                ])
                ->action(function (array $data): void {
                    if ($this->record->is_suspended) {
                        $this->record->update([
                            'is_suspended'      => false,
                            'is_active'         => true,
                            'suspended_at'      => null,
                            'suspension_reason' => null,
                        ]);
                        Notification::make()->title('Client réactivé')->success()->send();
                    } else {
                        $this->record->update([
                            'is_suspended'      => true,
                            'is_active'         => false,
                            'suspended_at'      => now(),
                            'suspension_reason' => $data['suspension_reason'] ?? null,
                        ]);
                        Notification::make()->title('Client suspendu')->warning()->send();
                    }
                    $this->refreshFormData(['is_suspended', 'is_active', 'suspended_at']);
                }),
        ];
    }
}
