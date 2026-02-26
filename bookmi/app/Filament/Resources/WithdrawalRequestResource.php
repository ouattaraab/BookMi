<?php

namespace App\Filament\Resources;

use App\Enums\WithdrawalStatus;
use App\Filament\Resources\WithdrawalRequestResource\Pages;
use App\Models\WithdrawalRequest;
use App\Notifications\WithdrawalStatusNotification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WithdrawalRequestResource extends Resource
{
    protected static ?string $model = WithdrawalRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Demandes de reversement';

    protected static ?string $modelLabel = 'Demande de reversement';

    protected static ?string $pluralModelLabel = 'Demandes de reversement';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 10;

    // Badge de navigation : nombre de demandes en attente
    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', WithdrawalStatus::Pending->value)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Talent')
                ->schema([
                    Forms\Components\TextInput::make('talentProfile.stage_name')
                        ->label('Nom de scène')
                        ->disabled(),
                    Forms\Components\TextInput::make('talentProfile.user.email')
                        ->label('Email')
                        ->disabled(),
                ])->columns(2),

            Forms\Components\Section::make('Demande')
                ->schema([
                    Forms\Components\TextInput::make('amount')
                        ->label('Montant demandé (XOF)')
                        ->disabled(),
                    Forms\Components\TextInput::make('status')
                        ->label('Statut')
                        ->disabled(),
                    Forms\Components\TextInput::make('payout_method')
                        ->label('Méthode de paiement')
                        ->disabled(),
                    Forms\Components\KeyValue::make('payout_details')
                        ->label('Coordonnées bancaires')
                        ->disabled(),
                    Forms\Components\DateTimePicker::make('created_at')
                        ->label('Date de la demande')
                        ->disabled(),
                    Forms\Components\DateTimePicker::make('processed_at')
                        ->label('Date de traitement')
                        ->disabled(),
                ])->columns(2),

            Forms\Components\Section::make('Note admin')
                ->schema([
                    Forms\Components\Textarea::make('note')
                        ->label('Note (visible par le talent si rejet)')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('talentProfile.stage_name')
                    ->label('Talent')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('talentProfile.user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant (XOF)')
                    ->numeric(thousandsSeparator: ' ')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payout_method')
                    ->label('Méthode')
                    ->formatStateUsing(fn ($state) => $state?->value ?? '—'),

                Tables\Columns\TextColumn::make('payout_details')
                    ->label('Compte')
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state['phone'] ?? $state['account_number'] ?? '—') : '—'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn (WithdrawalStatus $state) => $state->label())
                    ->color(fn (WithdrawalStatus $state) => $state->color()),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('processed_at')
                    ->label('Traitée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(collect(WithdrawalStatus::cases())->mapWithKeys(
                        fn (WithdrawalStatus $s) => [$s->value => $s->label()]
                    )->toArray()),
            ])
            ->actions([
                // ── Approuver ──────────────────────────────────────────────
                Tables\Actions\Action::make('approve')
                    ->label('Approuver')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approuver la demande de reversement')
                    ->modalDescription('Le talent sera notifié par email que sa demande a été approuvée.')
                    ->visible(fn (WithdrawalRequest $record) => $record->status === WithdrawalStatus::Pending)
                    ->action(function (WithdrawalRequest $record): void {
                        $record->update([
                            'status'       => WithdrawalStatus::Approved->value,
                            'processed_at' => now(),
                            'processed_by' => Auth::id(),
                        ]);

                        // Notifier le talent
                        $user = $record->talentProfile?->user;
                        if ($user) {
                            $user->notify(new WithdrawalStatusNotification($record));
                        }

                        Notification::make()
                            ->title('Demande approuvée')
                            ->success()
                            ->send();
                    }),

                // ── Marquer comme En cours ──────────────────────────────────
                Tables\Actions\Action::make('processing')
                    ->label('En cours')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Marquer comme en cours de traitement')
                    ->visible(fn (WithdrawalRequest $record) => $record->status === WithdrawalStatus::Approved)
                    ->action(function (WithdrawalRequest $record): void {
                        $record->update(['status' => WithdrawalStatus::Processing->value]);

                        $user = $record->talentProfile?->user;
                        if ($user) {
                            $user->notify(new WithdrawalStatusNotification($record));
                        }

                        Notification::make()
                            ->title('Statut mis à jour : En cours')
                            ->success()
                            ->send();
                    }),

                // ── Marquer comme Complété ──────────────────────────────────
                Tables\Actions\Action::make('complete')
                    ->label('Complété')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Confirmer le transfert effectué')
                    ->modalDescription('Le talent sera notifié que son reversement a été effectué.')
                    ->visible(fn (WithdrawalRequest $record) => $record->status === WithdrawalStatus::Processing)
                    ->action(function (WithdrawalRequest $record): void {
                        $record->update([
                            'status'       => WithdrawalStatus::Completed->value,
                            'processed_at' => now(),
                            'processed_by' => Auth::id(),
                        ]);

                        $user = $record->talentProfile?->user;
                        if ($user) {
                            $user->notify(new WithdrawalStatusNotification($record));
                        }

                        Notification::make()
                            ->title('Reversement marqué comme complété')
                            ->success()
                            ->send();
                    }),

                // ── Rejeter ─────────────────────────────────────────────────
                Tables\Actions\Action::make('reject')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('note')
                            ->label('Motif du rejet (optionnel, visible par le talent)')
                            ->rows(3),
                    ])
                    ->visible(fn (WithdrawalRequest $record) => in_array($record->status, [
                        WithdrawalStatus::Pending,
                        WithdrawalStatus::Approved,
                    ]))
                    ->action(function (WithdrawalRequest $record, array $data): void {
                        DB::transaction(function () use ($record, $data): void {
                            $record->update([
                                'status'       => WithdrawalStatus::Rejected->value,
                                'note'         => $data['note'] ?? null,
                                'processed_at' => now(),
                                'processed_by' => Auth::id(),
                            ]);

                            // Recréditer le solde disponible du talent
                            $record->talentProfile?->increment('available_balance', $record->amount);
                        });

                        $user = $record->talentProfile?->user;
                        if ($user) {
                            $user->notify(new WithdrawalStatusNotification($record));
                        }

                        Notification::make()
                            ->title('Demande rejetée — solde recrédité')
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
            'index' => Pages\ListWithdrawalRequests::route('/'),
            'view'  => Pages\ViewWithdrawalRequest::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['talentProfile.user']);
    }
}
