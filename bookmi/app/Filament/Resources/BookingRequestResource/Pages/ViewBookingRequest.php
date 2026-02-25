<?php

namespace App\Filament\Resources\BookingRequestResource\Pages;

use App\Enums\BookingStatus;
use App\Filament\Resources\BookingRequestResource;
use App\Jobs\GenerateContractPdf;
use App\Models\BookingRequest;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;

class ViewBookingRequest extends ViewRecord
{
    protected static string $resource = BookingRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download_contract')
                ->label('Télécharger le contrat')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(function (): bool {
                    /** @var BookingRequest $booking */
                    $booking = $this->record;
                    return $booking->contract_path !== null
                        && Storage::disk('local')->exists($booking->contract_path);
                })
                ->action(function () {
                    /** @var BookingRequest $booking */
                    $booking = $this->record;
                    return response()->streamDownload(
                        fn () => print(Storage::disk('local')->get($booking->contract_path)),
                        'contrat-reservation-' . $booking->id . '.pdf',
                        ['Content-Type' => 'application/pdf'],
                    );
                }),

            Actions\Action::make('regenerate_contract')
                ->label('Régénérer le contrat')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Régénérer le contrat PDF')
                ->modalDescription('Supprime le PDF existant et en génère un nouveau en arrière-plan.')
                ->visible(function (): bool {
                    /** @var BookingRequest $booking */
                    $booking = $this->record;
                    return in_array($booking->status, [
                        BookingStatus::Accepted,
                        BookingStatus::Paid,
                        BookingStatus::Confirmed,
                        BookingStatus::Completed,
                    ], true);
                })
                ->action(function (): void {
                    /** @var BookingRequest $booking */
                    $booking = $this->record;
                    if ($booking->contract_path && Storage::disk('local')->exists($booking->contract_path)) {
                        Storage::disk('local')->delete($booking->contract_path);
                    }
                    $booking->update(['contract_path' => null]);
                    GenerateContractPdf::dispatch($booking)->onQueue('media');
                    $this->refreshFormData(['contract_path']);
                })
                ->successNotificationTitle('Génération lancée — le PDF sera disponible dans quelques secondes.'),

            Actions\EditAction::make()
                ->label('Modifier / uploader contrat'),
        ];
    }
}
