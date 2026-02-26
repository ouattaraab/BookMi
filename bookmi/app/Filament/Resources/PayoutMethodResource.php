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
            ->where(
                fn (Builder $q) => $q
                ->whereNull('payout_method_status')
                ->orWhere('payout_method_status', 'pending')
            )
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    // Tous les profils ayant soumis un compte — toutes statuts confondus (traçabilité).
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNotNull('payout_method')
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
                    Forms\Components\TextInput::make('payout_method_status')
                        ->label('Statut')
                        ->formatStateUsing(fn ($state) => match ($state) {
                            'verified' => 'Validé',
                            'rejected' => 'Refusé',
                            default    => 'En attente',
                        })
                        ->disabled(),
                    Forms\Components\KeyValue::make('payout_details')
                        ->label('Coordonnées')
                        ->disabled(),
                    Forms\Components\DateTimePicker::make('payout_method_verified_at')
                        ->label('Validé le')
                        ->disabled(),
                    Forms\Components\DateTimePicker::make('updated_at')
                        ->label('Soumis le')
                        ->disabled(),
                    Forms\Components\Textarea::make('payout_method_rejection_reason')
                        ->label('Motif du refus')
                        ->disabled()
                        ->visible(fn ($record) => $record?->payout_method_status === 'rejected'),
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

                        return $state['phone']
                            ?? $state['account_number']
                            ?? implode(' / ', array_filter(array_values($state)));
                    }),

                Tables\Columns\BadgeColumn::make('payout_method_status')
                    ->label('Statut')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'verified' => 'Validé',
                        'rejected' => 'Refusé',
                        default    => 'En attente',
                    })
                    ->color(fn ($state) => match ($state) {
                        'verified' => 'success',
                        'rejected' => 'danger',
                        default    => 'warning',
                    }),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Soumis le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payout_method_status')
                    ->label('Statut')
                    ->options([
                        'pending'  => 'En attente',
                        'verified' => 'Validé',
                        'rejected' => 'Refusé',
                    ])
                    ->placeholder('Tous'),
            ])
            ->actions([
                // ── Valider ─────────────────────────────────────────────────
                Tables\Actions\Action::make('verify')
                    ->label('Valider')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Valider ce compte de paiement')
                    ->modalDescription('Le talent sera notifié par e-mail et pourra désormais effectuer des demandes de reversement.')
                    ->visible(
                        fn (TalentProfile $record): bool =>
                        $record->payout_method_status !== 'verified'
                    )
                    ->action(function (TalentProfile $record): void {
                        $record->update([
                            'payout_method_verified_at'       => now(),
                            'payout_method_verified_by'       => Auth::id(),
                            'payout_method_status'            => 'verified',
                            'payout_method_rejection_reason'  => null,
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
                    ->visible(
                        fn (TalentProfile $record): bool =>
                        $record->payout_method_status !== 'rejected'
                    )
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Motif du refus (visible par le talent)')
                            ->rows(3)
                            ->required(),
                    ])
                    ->action(function (TalentProfile $record, array $data): void {
                        // Conserver les données pour la traçabilité — ne pas effacer payout_method/details.
                        $record->update([
                            'payout_method_status'           => 'rejected',
                            'payout_method_rejection_reason' => $data['reason'],
                            'payout_method_verified_at'      => null,
                            'payout_method_verified_by'      => null,
                        ]);

                        $record->user?->notify(new PayoutMethodRejectedNotification($data['reason']));

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
            'view'  => Pages\ViewPayoutMethod::route('/{record}'),
        ];
    }
}
