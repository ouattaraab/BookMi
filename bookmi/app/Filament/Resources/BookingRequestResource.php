<?php

namespace App\Filament\Resources;

use App\Enums\BookingStatus;
use App\Filament\Resources\BookingRequestResource\Pages;
use App\Jobs\GenerateContractPdf;
use App\Models\BookingRequest;
use App\Models\User;
use App\Services\RefundService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class BookingRequestResource extends Resource
{
    protected static ?string $model = BookingRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return ($user?->is_admin ?? false) || ($user?->hasAnyRole(['admin_ceo', 'admin_controleur']) ?? false);
    }

    protected static ?string $navigationLabel = 'Réservations';

    protected static ?string $modelLabel = 'Réservation';

    protected static ?string $pluralModelLabel = 'Réservations';

    protected static ?string $navigationGroup = 'Activité';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Parties')
                ->schema([
                    Forms\Components\TextInput::make('client.email')
                        ->label('Client')
                        ->disabled(),
                    Forms\Components\TextInput::make('talentProfile.stage_name')
                        ->label('Talent')
                        ->disabled(),
                ])->columns(2),

            Forms\Components\Section::make('Détails de la prestation')
                ->schema([
                    Forms\Components\DatePicker::make('event_date')
                        ->label("Date de l'événement")
                        ->disabled(),
                    Forms\Components\TextInput::make('event_location')
                        ->label("Lieu de l'événement")
                        ->disabled(),
                    Forms\Components\Toggle::make('is_express')
                        ->label('Express')
                        ->disabled(),
                    Forms\Components\TextInput::make('status')
                        ->label('Statut')
                        ->disabled(),
                    Forms\Components\Textarea::make('message')
                        ->label('Message du client')
                        ->disabled()
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('reject_reason')
                        ->label('Motif de refus')
                        ->disabled()
                        ->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make('Montants')
                ->schema([
                    Forms\Components\TextInput::make('cachet_amount')
                        ->label('Cachet (FCFA)')
                        ->disabled(),
                    Forms\Components\TextInput::make('travel_cost')
                        ->label('Frais déplacement (FCFA)')
                        ->disabled(),
                    Forms\Components\TextInput::make('commission_amount')
                        ->label('Commission (FCFA)')
                        ->disabled(),
                    Forms\Components\TextInput::make('total_amount')
                        ->label('Total (FCFA)')
                        ->disabled(),
                ])->columns(4),

            Forms\Components\Section::make('Médiation')
                ->icon('heroicon-o-scale')
                ->visible(fn (?BookingRequest $record): bool => $record !== null
                    && ($record->status === BookingStatus::Disputed || $record->status === 'disputed'))
                ->schema([
                    Forms\Components\Select::make('mediator_id')
                        ->label('Médiateur assigné')
                        ->options(User::role('admin')->pluck('email', 'id'))
                        ->searchable()
                        ->nullable(),
                    Forms\Components\Textarea::make('mediation_notes')
                        ->label('Notes de médiation')
                        ->maxLength(2000)
                        ->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make('Contrat')
                ->icon('heroicon-o-document-text')
                ->schema([
                    Forms\Components\Placeholder::make('contract_status')
                        ->label('Statut du contrat')
                        ->content(fn (BookingRequest $record): string => $record->contract_path
                            ? '✅ PDF généré — ' . $record->contract_path
                            : '❌ Aucun contrat généré'),

                    Forms\Components\FileUpload::make('contract_path')
                        ->label('Uploader un PDF manuellement')
                        ->disk('local')
                        ->directory('contracts')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(10240)
                        ->helperText('Remplace le contrat existant si présent.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('client.email')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('talentProfile.stage_name')
                    ->label('Talent')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('event_date')
                    ->label('Date événement')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('event_location')
                    ->label('Lieu')
                    ->limit(30)
                    ->tooltip(fn ($state) => $state),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn ($state): string => match (true) {
                        $state === BookingStatus::Pending || $state === 'pending'     => 'En attente',
                        $state === BookingStatus::Accepted || $state === 'accepted'   => 'Acceptée',
                        $state === BookingStatus::Paid || $state === 'paid'           => 'Payée',
                        $state === BookingStatus::Confirmed || $state === 'confirmed' => 'Confirmée',
                        $state === BookingStatus::Completed || $state === 'completed' => 'Terminée',
                        $state === BookingStatus::Cancelled || $state === 'cancelled' => 'Annulée',
                        $state === BookingStatus::Disputed || $state === 'disputed'   => 'Litige',
                        default => (string) $state,
                    })
                    ->color(fn ($state): string => match (true) {
                        $state === BookingStatus::Pending || $state === 'pending'     => 'warning',
                        $state === BookingStatus::Accepted || $state === 'accepted'   => 'info',
                        $state === BookingStatus::Paid || $state === 'paid'           => 'info',
                        $state === BookingStatus::Confirmed || $state === 'confirmed' => 'primary',
                        $state === BookingStatus::Completed || $state === 'completed' => 'success',
                        $state === BookingStatus::Cancelled || $state === 'cancelled' => 'danger',
                        $state === BookingStatus::Disputed || $state === 'disputed'   => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('dispute_age')
                    ->label('Âge litige')
                    ->state(fn (BookingRequest $record): string => $record->status === BookingStatus::Disputed || $record->status === 'disputed'
                        ? $record->updated_at->diffForHumans()
                        : '—')
                    ->color(fn (BookingRequest $record): string => ($record->status === BookingStatus::Disputed || $record->status === 'disputed')
                        && $record->updated_at->diffInHours() > 48
                        ? 'danger'
                        : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('travel_cost')
                    ->label('Déplacement')
                    ->formatStateUsing(fn ($state): string => $state > 0
                        ? number_format((int) $state, 0, ',', ' ') . ' FCFA'
                        : '—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('mediator.email')
                    ->label('Médiateur')
                    ->default('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->formatStateUsing(fn ($state): string => number_format((int) $state, 0, ',', ' ') . ' FCFA')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_express')
                    ->label('Express')
                    ->boolean()
                    ->trueColor('warning')
                    ->falseColor('gray'),

                Tables\Columns\IconColumn::make('contract_path')
                    ->label('Contrat')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-check')
                    ->falseIcon('heroicon-o-document-minus')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->getStateUsing(fn (BookingRequest $record): bool => $record->contract_path !== null),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending'   => 'En attente',
                        'accepted'  => 'Acceptée',
                        'paid'      => 'Payée',
                        'confirmed' => 'Confirmée',
                        'completed' => 'Terminée',
                        'cancelled' => 'Annulée',
                        'disputed'  => 'Litige',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Voir'),

                Tables\Actions\Action::make('download_contract')
                    ->label('Contrat')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn (BookingRequest $record): bool =>
                        $record->contract_path !== null
                        && Storage::disk('local')->exists($record->contract_path))
                    ->action(fn (BookingRequest $record) => response()->streamDownload(
                        fn () => print(Storage::disk('local')->get($record->contract_path)),
                        'contrat-reservation-' . $record->id . '.pdf',
                        ['Content-Type' => 'application/pdf'],
                    )),

                Tables\Actions\Action::make('regenerate_contract')
                    ->label('Régénérer')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Régénérer le contrat')
                    ->modalDescription('Le contrat PDF existant sera supprimé et un nouveau sera généré.')
                    ->visible(fn (BookingRequest $record): bool =>
                        in_array($record->status, [
                            BookingStatus::Accepted,
                            BookingStatus::Paid,
                            BookingStatus::Confirmed,
                            BookingStatus::Completed,
                        ], true))
                    ->action(function (BookingRequest $record): void {
                        if ($record->contract_path && Storage::disk('local')->exists($record->contract_path)) {
                            Storage::disk('local')->delete($record->contract_path);
                        }
                        $record->update(['contract_path' => null]);
                        GenerateContractPdf::dispatchSync($record);
                    })
                    ->successNotificationTitle('Contrat régénéré avec succès'),

                Tables\Actions\Action::make('refund')
                    ->label('Rembourser')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn (BookingRequest $record): bool =>
                        $record->status === BookingStatus::Disputed || $record->status === 'disputed')
                    ->requiresConfirmation()
                    ->modalHeading('Confirmer le remboursement')
                    ->modalDescription('Le client sera remboursé du montant total de la réservation. Cette action est irréversible.')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Motif du remboursement')
                            ->required()
                            ->minLength(10)
                            ->maxLength(500)
                            ->placeholder('Ex : Litige résolu en faveur du client — prestation non effectuée.'),
                    ])
                    ->action(function (BookingRequest $record, array $data): void {
                        try {
                            app(RefundService::class)->processRefund(
                                $record,
                                $record->total_amount,
                                $data['reason'],
                            );
                            Notification::make()->title('Remboursement effectué.')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Erreur : ' . $e->getMessage())->danger()->send();
                        }
                    }),

                Tables\Actions\Action::make('assign_mediator')
                    ->label('Médiateur')
                    ->icon('heroicon-o-scale')
                    ->color('warning')
                    ->visible(fn (BookingRequest $record): bool =>
                        $record->status === BookingStatus::Disputed || $record->status === 'disputed')
                    ->form([
                        Forms\Components\Select::make('mediator_id')
                            ->label('Médiateur')
                            ->options(User::role('admin')->pluck('email', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\Textarea::make('mediation_notes')
                            ->label('Notes de médiation')
                            ->maxLength(2000),
                    ])
                    ->fillForm(fn (BookingRequest $record): array => [
                        'mediator_id'     => $record->mediator_id,
                        'mediation_notes' => $record->mediation_notes,
                    ])
                    ->action(function (BookingRequest $record, array $data): void {
                        $record->update([
                            'mediator_id'     => $data['mediator_id'],
                            'mediation_notes' => $data['mediation_notes'] ?? null,
                        ]);
                        Notification::make()->title('Médiateur assigné.')->success()->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->label('Supprimer'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBookingRequests::route('/'),
            'view'   => Pages\ViewBookingRequest::route('/{record}'),
            'edit'   => Pages\EditBookingRequest::route('/{record}/edit'),
        ];
    }
}
