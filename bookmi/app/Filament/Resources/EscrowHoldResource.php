<?php

namespace App\Filament\Resources;

use App\Enums\EscrowStatus;
use App\Filament\Resources\EscrowHoldResource\Pages;
use App\Models\EscrowHold;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EscrowHoldResource extends Resource
{
    protected static ?string $model = EscrowHold::class;

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $navigationLabel = 'Escrow';

    protected static ?string $modelLabel = 'Escrow';

    protected static ?string $pluralModelLabel = 'Escrow';

    protected static ?string $navigationGroup = 'Finances';

    protected static ?int $navigationSort = 8;

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
        $count = static::getModel()::where('status', EscrowStatus::Held->value)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Escrow')
                ->schema([
                    Forms\Components\TextInput::make('booking_request_id')
                        ->label('Réservation #')
                        ->disabled(),
                    Forms\Components\TextInput::make('transaction_id')
                        ->label('Transaction #')
                        ->disabled(),
                    Forms\Components\TextInput::make('cachet_amount')
                        ->label('Cachet (XOF)')
                        ->disabled(),
                    Forms\Components\TextInput::make('commission_amount')
                        ->label('Commission (XOF)')
                        ->disabled(),
                    Forms\Components\TextInput::make('total_amount')
                        ->label('Total (XOF)')
                        ->disabled(),
                    Forms\Components\TextInput::make('status')
                        ->label('Statut')
                        ->formatStateUsing(fn ($state) => $state instanceof EscrowStatus ? $state->value : $state)
                        ->disabled(),
                    Forms\Components\DateTimePicker::make('held_at')
                        ->label('Bloqué le')
                        ->disabled(),
                    Forms\Components\DateTimePicker::make('release_scheduled_at')
                        ->label('Libération prévue le')
                        ->disabled(),
                    Forms\Components\DateTimePicker::make('released_at')
                        ->label('Libéré le')
                        ->disabled(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('held_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('booking_request_id')
                    ->label('Réservation')
                    ->sortable(),

                Tables\Columns\TextColumn::make('cachet_amount')
                    ->label('Cachet (XOF)')
                    ->numeric(thousandsSeparator: ' ')
                    ->sortable(),

                Tables\Columns\TextColumn::make('commission_amount')
                    ->label('Commission (XOF)')
                    ->numeric(thousandsSeparator: ' ')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total (XOF)')
                    ->numeric(thousandsSeparator: ' ')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof EscrowStatus ? $state->value : $state)
                    ->color(fn ($state): string => match (true) {
                        $state === EscrowStatus::Held || $state === 'held'         => 'warning',
                        $state === EscrowStatus::Released || $state === 'released' => 'success',
                        $state === EscrowStatus::Refunded || $state === 'refunded' => 'info',
                        $state === EscrowStatus::Disputed || $state === 'disputed' => 'danger',
                        default                                                    => 'gray',
                    }),

                Tables\Columns\TextColumn::make('held_at')
                    ->label('Bloqué le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('release_scheduled_at')
                    ->label('Libération prévue')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('released_at')
                    ->label('Libéré le')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(collect(EscrowStatus::cases())->mapWithKeys(
                        fn (EscrowStatus $s) => [$s->value => $s->value]
                    )->toArray()),
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
            'index' => Pages\ListEscrowHolds::route('/'),
            'view'  => Pages\ViewEscrowHold::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['bookingRequest']);
    }
}
