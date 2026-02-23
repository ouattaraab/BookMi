<?php

namespace App\Filament\Resources\IdentityVerificationResource\Pages;

use App\Enums\VerificationStatus;
use App\Filament\Resources\IdentityVerificationResource;
use App\Services\ActivityLogger;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewIdentityVerification extends ViewRecord
{
    protected static string $resource = IdentityVerificationResource::class;

    protected function hasInfolist(): bool
    {
        return true;
    }

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

                    ActivityLogger::log('verification.approved', $this->record, [
                        'user_email'    => $this->record->user?->email,
                        'document_type' => $this->record->document_type,
                    ]);

                    Notification::make()->title('Vérification approuvée')->success()->send();
                    $this->record->refresh();
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

                    ActivityLogger::log('verification.rejected', $this->record, [
                        'user_email'       => $this->record->user?->email,
                        'document_type'    => $this->record->document_type,
                        'rejection_reason' => $data['rejection_reason'],
                    ]);

                    Notification::make()->title('Vérification rejetée')->danger()->send();
                    $this->record->refresh();
                }),
        ];
    }
}
