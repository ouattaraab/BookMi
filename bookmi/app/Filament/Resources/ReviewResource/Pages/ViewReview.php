<?php

namespace App\Filament\Resources\ReviewResource\Pages;

use App\Filament\Resources\ReviewResource;
use App\Services\ActivityLogger;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewReview extends ViewRecord
{
    protected static string $resource = ReviewResource::class;

    protected function hasInfolist(): bool
    {
        return true;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Valider (retirer le signalement)')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => (bool) $this->record->is_reported)
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->update([
                        'is_reported'   => false,
                        'report_reason' => null,
                        'reported_at'   => null,
                    ]);

                    ActivityLogger::log('review.report_dismissed', $this->record, [
                        'reviewer_email' => $this->record->reviewer?->email,
                        'reviewee_email' => $this->record->reviewee?->email,
                        'rating'         => $this->record->rating,
                    ]);

                    Notification::make()->title('Signalement retiré')->success()->send();
                    $this->record->refresh();
                }),

            Actions\DeleteAction::make()
                ->label('Supprimer cet avis'),
        ];
    }
}
