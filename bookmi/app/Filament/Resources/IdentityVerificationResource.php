<?php

namespace App\Filament\Resources;

use App\Enums\VerificationStatus;
use App\Filament\Resources\IdentityVerificationResource\Pages;
use App\Models\IdentityVerification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class IdentityVerificationResource extends Resource
{
    protected static ?string $model = IdentityVerification::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationLabel = "Vérifications d'identité";

    protected static ?string $modelLabel = "Vérification d'identité";

    protected static ?string $pluralModelLabel = "Vérifications d'identité";

    protected static ?string $navigationGroup = 'Gestion des utilisateurs';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $count = IdentityVerification::where('verification_status', 'pending')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Demandeur')
                ->schema([
                    Forms\Components\TextInput::make('user.email')
                        ->label('Email utilisateur')
                        ->disabled(),
                    Forms\Components\TextInput::make('document_type')
                        ->label('Type de document')
                        ->disabled(),
                ])->columns(2),

            Forms\Components\Section::make('Statut de vérification')
                ->schema([
                    Forms\Components\TextInput::make('verification_status')
                        ->label('Statut')
                        ->formatStateUsing(fn ($state) => $state instanceof VerificationStatus ? $state->label() : $state)
                        ->disabled(),
                    Forms\Components\DateTimePicker::make('reviewed_at')
                        ->label('Examiné le')
                        ->disabled(),
                    Forms\Components\DateTimePicker::make('verified_at')
                        ->label('Vérifié le')
                        ->disabled(),
                    Forms\Components\Textarea::make('rejection_reason')
                        ->label('Motif de rejet')
                        ->disabled()
                        ->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make('Document')
                ->schema([
                    Forms\Components\TextInput::make('stored_path')
                        ->label('Chemin du document')
                        ->disabled()
                        ->suffixAction(
                            Forms\Components\Actions\Action::make('open_document')
                                ->label('Ouvrir')
                                ->icon('heroicon-o-arrow-top-right-on-square')
                                ->url(fn ($state) => $state ? asset('storage/' . $state) : null)
                                ->openUrlInNewTab()
                        ),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Utilisateur')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('document_type')
                    ->label('Type de document')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('verification_status')
                    ->label('Statut')
                    ->formatStateUsing(fn ($state): string => $state instanceof VerificationStatus ? $state->label() : (string) $state)
                    ->color(fn ($state): string => match (true) {
                        $state === VerificationStatus::PENDING || $state === 'pending'   => 'warning',
                        $state === VerificationStatus::APPROVED || $state === 'approved' => 'success',
                        $state === VerificationStatus::REJECTED || $state === 'rejected' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Soumis le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reviewed_at')
                    ->label('Examiné le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('verification_status')
                    ->label('Statut')
                    ->options([
                        'pending'  => 'En attente',
                        'approved' => 'Approuvé',
                        'rejected' => 'Rejeté',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Voir'),

                Tables\Actions\Action::make('approve')
                    ->label('Approuver')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (IdentityVerification $record): bool => $record->verification_status === VerificationStatus::PENDING)
                    ->requiresConfirmation()
                    ->modalHeading('Approuver la vérification ?')
                    ->modalDescription('Le profil talent de cet utilisateur sera marqué comme vérifié.')
                    ->action(function (IdentityVerification $record): void {
                        $record->update([
                            'verification_status' => VerificationStatus::APPROVED,
                            'verified_at'         => now(),
                            'reviewed_at'         => now(),
                            'reviewer_id'         => Auth::id(),
                        ]);

                        if ($record->user?->talentProfile) {
                            $record->user->talentProfile->update(['is_verified' => true]);
                        }

                        Notification::make()
                            ->title('Vérification approuvée')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (IdentityVerification $record): bool => $record->verification_status === VerificationStatus::PENDING)
                    ->modalHeading('Rejeter la vérification')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Motif de rejet')
                            ->required()
                            ->maxLength(500)
                            ->placeholder('Expliquez pourquoi ce document est rejeté…'),
                    ])
                    ->action(function (IdentityVerification $record, array $data): void {
                        $record->update([
                            'verification_status' => VerificationStatus::REJECTED,
                            'rejection_reason'    => $data['rejection_reason'],
                            'reviewed_at'         => now(),
                            'reviewer_id'         => Auth::id(),
                        ]);

                        Notification::make()
                            ->title('Vérification rejetée')
                            ->danger()
                            ->send();
                    }),
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
            'index' => Pages\ListIdentityVerifications::route('/'),
            'view'  => Pages\ViewIdentityVerification::route('/{record}'),
        ];
    }
}
