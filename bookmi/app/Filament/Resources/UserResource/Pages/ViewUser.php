<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('toggle_suspend')
                ->label(fn (): string => $this->record->is_suspended ? 'Réactiver' : 'Suspendre')
                ->icon(fn (): string => $this->record->is_suspended ? 'heroicon-o-check-circle' : 'heroicon-o-no-symbol')
                ->color(fn (): string => $this->record->is_suspended ? 'success' : 'danger')
                ->requiresConfirmation()
                ->action(function (): void {
                    if ($this->record->is_suspended) {
                        $this->record->update([
                            'is_suspended'     => false,
                            'is_active'        => true,
                            'suspended_at'     => null,
                            'suspension_reason' => null,
                        ]);
                    } else {
                        $this->record->update([
                            'is_suspended' => true,
                            'is_active'    => false,
                            'suspended_at' => now(),
                        ]);
                    }
                    $this->refreshFormData(['is_suspended', 'is_active', 'suspended_at']);
                }),
        ];
    }
}
