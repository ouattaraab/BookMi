<?php

namespace App\Filament\Resources\TalentResource\Pages;

use App\Filament\Resources\TalentResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewTalent extends ViewRecord
{
    protected static string $resource = TalentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('toggle_verify')
                ->label(fn (): string => $this->record->talentProfile?->is_verified ? 'Désactiver vérif.' : 'Vérifier')
                ->icon(fn (): string => $this->record->talentProfile?->is_verified ? 'heroicon-o-shield-exclamation' : 'heroicon-o-shield-check')
                ->color(fn (): string => $this->record->talentProfile?->is_verified ? 'warning' : 'success')
                ->visible(fn (): bool => $this->record->talentProfile !== null)
                ->requiresConfirmation()
                ->action(function (): void {
                    $profile = $this->record->talentProfile;
                    if ($profile) {
                        $newValue = ! $profile->is_verified;
                        $profile->update(['is_verified' => $newValue]);
                        Notification::make()
                            ->title($newValue ? 'Talent vérifié' : 'Vérification retirée')
                            ->success()
                            ->send();
                        $this->record->refresh();
                    }
                }),

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
                        Notification::make()->title('Talent réactivé')->success()->send();
                    } else {
                        $this->record->update([
                            'is_suspended'      => true,
                            'is_active'         => false,
                            'suspended_at'      => now(),
                            'suspension_reason' => $data['suspension_reason'] ?? null,
                        ]);
                        Notification::make()->title('Talent suspendu')->warning()->send();
                    }
                    $this->refreshFormData(['is_suspended', 'is_active', 'suspended_at']);
                }),
        ];
    }
}
