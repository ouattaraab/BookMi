<?php

namespace App\Filament\Resources\BookingRequestResource\Pages;

use App\Enums\BookingStatus;
use App\Filament\Resources\BookingRequestResource;
use App\Jobs\GenerateContractPdf;
use App\Models\BookingRequest;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ViewBookingRequest extends ViewRecord
{
    protected static string $resource = BookingRequestResource::class;

    /**
     * Inject the client and talentProfile relationships so that client.email
     * and talentProfile.stage_name form fields are populated.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var BookingRequest $booking */
        $booking = $this->record;

        $client = $booking->client;
        if ($client) {
            $data['client'] = $client->toArray();
        }

        $talentProfile = $booking->talentProfile;
        if ($talentProfile) {
            $data['talentProfile'] = $talentProfile->toArray();
        }

        return $data;
    }

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

            Actions\Action::make('view_timeline')
                ->label('Chronologie')
                ->icon('heroicon-o-clock')
                ->color('info')
                ->slideOver()
                ->modalContent(function (): View {
                    /** @var BookingRequest $booking */
                    $booking = $this->record;
                    $logs = $booking->statusLogs()->with('performer')->get();

                    return view('filament.booking-timeline', [
                        'logs'    => $logs,
                        'booking' => $booking,
                    ]);
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Fermer'),

            Actions\EditAction::make()
                ->label('Modifier / uploader contrat'),
        ];
    }
}
