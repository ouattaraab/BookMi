<?php

namespace App\Filament\Resources;

use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Transactions';

    protected static ?string $modelLabel = 'Transaction';

    protected static ?string $pluralModelLabel = 'Transactions';

    protected static ?string $navigationGroup = 'Finances';

    protected static ?int $navigationSort = 5;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return ($user?->is_admin === true) || ($user?->hasAnyRole(['admin_ceo', 'admin_comptable']) ?? false);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', TransactionStatus::Processing->value)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Transaction')
                ->schema([
                    Forms\Components\TextInput::make('id')
                        ->label('#')
                        ->disabled(),
                    Forms\Components\TextInput::make('booking_request_id')
                        ->label('Réservation #')
                        ->disabled(),
                    Forms\Components\TextInput::make('payment_method')
                        ->label('Méthode de paiement')
                        ->formatStateUsing(fn ($state) => $state instanceof PaymentMethod ? $state->value : $state)
                        ->disabled(),
                    Forms\Components\TextInput::make('amount')
                        ->label('Montant (XOF)')
                        ->disabled(),
                    Forms\Components\TextInput::make('currency')
                        ->label('Devise')
                        ->disabled(),
                    Forms\Components\TextInput::make('status')
                        ->label('Statut')
                        ->formatStateUsing(fn ($state) => $state instanceof TransactionStatus ? $state->value : $state)
                        ->disabled(),
                    Forms\Components\TextInput::make('gateway')
                        ->label('Passerelle')
                        ->disabled(),
                    Forms\Components\TextInput::make('gateway_reference')
                        ->label('Référence passerelle')
                        ->disabled(),
                    Forms\Components\TextInput::make('idempotency_key')
                        ->label('Clé d\'idempotence')
                        ->disabled(),
                    Forms\Components\DateTimePicker::make('initiated_at')
                        ->label('Initiée le')
                        ->disabled(),
                    Forms\Components\DateTimePicker::make('completed_at')
                        ->label('Complétée le')
                        ->disabled(),
                ])->columns(2),

            Forms\Components\Section::make('Remboursement')
                ->schema([
                    Forms\Components\TextInput::make('refund_amount')
                        ->label('Montant remboursé (XOF)')
                        ->disabled(),
                    Forms\Components\TextInput::make('refund_reference')
                        ->label('Référence remboursement')
                        ->disabled(),
                    Forms\Components\Textarea::make('refund_reason')
                        ->label('Motif du remboursement')
                        ->disabled()
                        ->columnSpanFull(),
                    Forms\Components\DateTimePicker::make('refunded_at')
                        ->label('Remboursé le')
                        ->disabled(),
                ])->columns(2),

            Forms\Components\Section::make('Réponse passerelle')
                ->schema([
                    Forms\Components\KeyValue::make('gateway_response')
                        ->label('Données brutes')
                        ->disabled(),
                ])->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('initiated_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('booking_request_id')
                    ->label('Réservation')
                    ->sortable()
                    ->url(fn (Transaction $record): ?string => $record->booking_request_id
                        ? BookingRequestResource::getUrl('view', ['record' => $record->booking_request_id])
                        : null),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Méthode')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof PaymentMethod ? $state->value : $state)
                    ->color(fn ($state): string => match (true) {
                        $state === PaymentMethod::Card || $state === 'card'                                                                                            => 'primary',
                        in_array($state, [PaymentMethod::OrangeMoney, PaymentMethod::Wave, PaymentMethod::MtnMomo, PaymentMethod::MoovMoney], true)
                        || in_array($state, ['orange_money', 'wave', 'mtn_momo', 'moov_money'], true)                                                                 => 'warning',
                        $state === PaymentMethod::BankTransfer || $state === 'bank_transfer'                                                                          => 'info',
                        default                                                                                                                                       => 'gray',
                    }),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant (XOF)')
                    ->numeric(thousandsSeparator: ' ')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof TransactionStatus ? $state->value : $state)
                    ->color(fn ($state): string => match (true) {
                        $state === TransactionStatus::Initiated || $state === 'initiated'   => 'gray',
                        $state === TransactionStatus::Processing || $state === 'processing' => 'warning',
                        $state === TransactionStatus::Succeeded || $state === 'succeeded'   => 'success',
                        $state === TransactionStatus::Failed || $state === 'failed'         => 'danger',
                        $state === TransactionStatus::Refunded || $state === 'refunded'     => 'info',
                        default                                                             => 'gray',
                    }),

                Tables\Columns\TextColumn::make('gateway')
                    ->label('Passerelle'),

                Tables\Columns\TextColumn::make('initiated_at')
                    ->label('Initiée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Complétée le')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('refund_amount')
                    ->label('Remboursé (XOF)')
                    ->numeric(thousandsSeparator: ' ')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(collect(TransactionStatus::cases())->mapWithKeys(
                        fn (TransactionStatus $s) => [$s->value => $s->value]
                    )->toArray()),

                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Méthode de paiement')
                    ->options(collect(PaymentMethod::cases())->mapWithKeys(
                        fn (PaymentMethod $m) => [$m->value => $m->value]
                    )->toArray()),

                Tables\Filters\Filter::make('initiated_at_range')
                    ->label('Période d\'initiation')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Du'),
                        Forms\Components\DatePicker::make('until')->label('Au'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('initiated_at', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('initiated_at', '<=', $data['until']));
                    }),
            ])
            ->actions([
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
            'index' => Pages\ListTransactions::route('/'),
            'view'  => Pages\ViewTransaction::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['bookingRequest']);
    }
}
