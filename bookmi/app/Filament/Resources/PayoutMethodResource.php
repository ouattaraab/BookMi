<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayoutMethodResource\Pages;
use App\Models\TalentProfile;
use App\Notifications\PayoutMethodRejectedNotification;
use App\Notifications\PayoutMethodVerifiedNotification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PayoutMethodResource extends Resource
{
    protected static ?string $model = TalentProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Validations de comptes';

    protected static ?string $modelLabel = 'Compte de paiement';

    protected static ?string $pluralModelLabel = 'Comptes de paiement';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'payout-methods';

    // Badge : nombre de comptes en attente de validation
    public static function getNavigationBadge(): ?string
    {
        $count = TalentProfile::whereNotNull('payout_method')
            ->whereNull('payout_method_verified_at')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    // Ne lister que les profils avec un compte soumis et non encore validé
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNotNull('payout_method')
            ->whereNull('payout_method_verified_at')
            ->with('user');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Talent')
                ->schema([
                    Forms\Components\TextInput::make('user.first_name')
                        ->label('Prénom')
                        ->disabled(),
                    Forms\Components\TextInput::make('user.last_name')
                        ->label('Nom')
                        ->disabled(),
                    Forms\Components\TextInput::make('user.email')
                        ->label('Email')
                        ->disabled(),
                    Forms\Components\TextInput::make('stage_name')
                        ->label('Nom de scène')
                        ->disabled(),
                ])->columns(2),

            Forms\Components\Section::make('Compte de paiement soumis')
                ->schema([
                    Forms\Components\TextInput::make('payout_method')
                        ->label('Méthode')
                        ->formatStateUsing(fn ($state) => $state ?? '—')
                        ->disabled(),
                    Forms\Components\KeyValue::make('payout_details')
                        ->label('Coordonnées')
                        ->disabled(),
                    Forms\Components\DateTimePicker::make('updated_at')
                        ->label('Soumis le')
                        ->disabled(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('stage_name')
                    ->label('Nom de scène')
                    ->searchable()
                    ->sortable()
                    ->default('—'),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('payout_method')
                    ->label('Méthode')
                    ->formatStateUsing(fn ($state) => $state ?? '—')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('payout_details')
                    ->label('Coordonnées')
                    ->formatStateUsing(function ($state): string {
                        if (! is_array($state) || empty($state)) {
                            return '—';
                        }

                        // Prefer the phone key for mobile money methods,
                        // then account_number for bank transfer,
                        // otherwise join all values (covers any future key).
                        return $state['phone']
                            ?? $state['account_number']
                            ?? implode(' / ', array_filter(array_values($state)));
                    }),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Soumis le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                // ── Valider ─────────────────────────────────────────────────
                Tables\Actions\Action::make('verify')
                    ->label('Valider')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Valider ce compte de paiement')
                    ->modalDescription('Le talent sera notifié par e-mail et pourra désormais effectuer des demandes de reversement.')
                    ->action(function (TalentProfile $record): void {
                        $record->update([
                            'payout_method_verified_at' => now(),
                            'payout_method_verified_by' => Auth::id(),
                        ]);

                        $record->user?->notify(new PayoutMethodVerifiedNotification($record));

                        Notification::make()
                            ->title('Compte validé — talent notifié')
                            ->success()
                            ->send();
                    }),

                // ── Refuser ──────────────────────────────────────────────────
                Tables\Actions\Action::make('reject')
                    ->label('Refuser')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Motif du refus (visible par le talent)')
                            ->rows(3)
                            ->required(),
                    ])
                    ->action(function (TalentProfile $record, array $data): void {
                        // Notifier le talent avant de vider les données
                        $record->user?->notify(new PayoutMethodRejectedNotification($data['reason']));

                        // Effacer le compte soumis — le talent devra en renseigner un nouveau
                        $record->update([
                            'payout_method' => null,
                            'payout_details' => null,
                            'payout_method_verified_at' => null,
                            'payout_method_verified_by' => null,
                        ]);

                        Notification::make()
                            ->title('Compte refusé — talent notifié')
                            ->warning()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayoutMethods::route('/'),
            'view' => Pages\ViewPayoutMethod::route('/{record}'),
        ];
    }
}
