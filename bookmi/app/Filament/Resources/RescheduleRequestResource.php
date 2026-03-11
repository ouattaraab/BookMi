<?php

namespace App\Filament\Resources;

use App\Enums\RescheduleStatus;
use App\Filament\Resources\RescheduleRequestResource\Pages;
use App\Models\RescheduleRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RescheduleRequestResource extends Resource
{
    protected static ?string $model = RescheduleRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Reports de dates';

    protected static ?string $modelLabel = 'Report de date';

    protected static ?string $pluralModelLabel = 'Reports de dates';

    protected static ?string $navigationGroup = 'Réservations';

    protected static ?int $navigationSort = 25;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return ($user?->is_admin === true) || ($user?->hasAnyRole(['admin_ceo', 'admin_moderation']) ?? false);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', RescheduleStatus::Pending->value)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Demande de report')
                ->schema([
                    Forms\Components\TextInput::make('booking_request_id')
                        ->label('Réservation #')
                        ->disabled(),
                    Forms\Components\TextInput::make('requestedBy.name')
                        ->label('Demandé par')
                        ->disabled(),
                    Forms\Components\DatePicker::make('proposed_date')
                        ->label('Date proposée')
                        ->disabled(),
                    Forms\Components\TextInput::make('status')
                        ->label('Statut')
                        ->formatStateUsing(fn ($state) => $state instanceof RescheduleStatus ? $state->value : $state)
                        ->disabled(),
                    Forms\Components\Textarea::make('message')
                        ->label('Message')
                        ->disabled()
                        ->columnSpanFull(),
                    Forms\Components\DateTimePicker::make('responded_at')
                        ->label('Répondu le')
                        ->disabled(),
                    Forms\Components\DateTimePicker::make('created_at')
                        ->label('Demandé le')
                        ->disabled(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('booking_request_id')
                    ->label('Réservation')
                    ->sortable()
                    ->url(fn (RescheduleRequest $record): ?string => $record->booking_request_id
                        ? BookingRequestResource::getUrl('view', ['record' => $record->booking_request_id])
                        : null),

                Tables\Columns\TextColumn::make('requestedBy.name')
                    ->label('Demandé par')
                    ->default('—'),

                Tables\Columns\TextColumn::make('proposed_date')
                    ->label('Date proposée')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('message')
                    ->label('Message')
                    ->limit(60)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof RescheduleStatus ? $state->value : $state)
                    ->color(fn ($state): string => match (true) {
                        $state === RescheduleStatus::Pending || $state === 'pending'   => 'warning',
                        $state === RescheduleStatus::Accepted || $state === 'accepted' => 'success',
                        $state === RescheduleStatus::Rejected || $state === 'rejected' => 'danger',
                        default                                                        => 'gray',
                    }),

                Tables\Columns\TextColumn::make('responded_at')
                    ->label('Répondu le')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Demandé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(collect(RescheduleStatus::cases())->mapWithKeys(
                        fn (RescheduleStatus $s) => [$s->value => $s->value]
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
            'index' => Pages\ListRescheduleRequests::route('/'),
            'view'  => Pages\ViewRescheduleRequest::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['requestedBy']);
    }
}
