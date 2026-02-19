<?php

namespace App\Filament\Resources\IdentityVerificationResource\Pages;

use App\Enums\VerificationStatus;
use App\Filament\Resources\IdentityVerificationResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewIdentityVerification extends ViewRecord
{
    protected static string $resource = IdentityVerificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Approuver')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => $this->record->verification_status === VerificationStatus::PENDING)
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->update([
                        'verification_status' => VerificationStatus::APPROVED,
                        'verified_at'         => now(),
                        'reviewed_at'         => now(),
                        'reviewer_id'         => Auth::id(),
                    ]);
                    if ($this->record->user?->talentProfile) {
                        $this->record->user->talentProfile->update(['is_verified' => true]);
                    }
                    Notification::make()->title('Vérification approuvée')->success()->send();
                    $this->refreshFormData(['verification_status', 'verified_at', 'reviewed_at']);
                }),

            Actions\Action::make('reject')
                ->label('Rejeter')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => $this->record->verification_status === VerificationStatus::PENDING)
                ->form([
                    \Filament\Forms\Components\Textarea::make('rejection_reason')
                        ->label('Motif de rejet')
                        ->required()
                        ->maxLength(500),
                ])
                ->action(function (array $data): void {
                    $this->record->update([
                        'verification_status' => VerificationStatus::REJECTED,
                        'rejection_reason'    => $data['rejection_reason'],
                        'reviewed_at'         => now(),
                        'reviewer_id'         => Auth::id(),
                    ]);
                    Notification::make()->title('Vérification rejetée')->danger()->send();
                    $this->refreshFormData(['verification_status', 'rejection_reason', 'reviewed_at']);
                }),
        ];
    }
}
