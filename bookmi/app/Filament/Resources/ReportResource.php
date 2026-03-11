<?php

namespace App\Filament\Resources;

use App\Enums\ReportReason;
use App\Filament\Resources\ReportResource\Pages;
use App\Models\Report;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationLabel = 'Signalements';

    protected static ?string $modelLabel = 'Signalement';

    protected static ?string $pluralModelLabel = 'Signalements';

    protected static ?string $navigationGroup = 'Modération';

    protected static ?int $navigationSort = 10;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return ($user?->is_admin === true) || ($user?->hasAnyRole(['admin_ceo', 'admin_moderation']) ?? false);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'pending')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Signalement')
                ->schema([
                    Forms\Components\TextInput::make('reporter.name')
                        ->label('Signalant')
                        ->disabled(),
                    Forms\Components\TextInput::make('booking_request_id')
                        ->label('Réservation #')
                        ->disabled(),
                    Forms\Components\TextInput::make('reason')
                        ->label('Motif')
                        ->formatStateUsing(fn ($state) => $state instanceof ReportReason ? $state->value : $state)
                        ->disabled(),
                    Forms\Components\TextInput::make('status')
                        ->label('Statut')
                        ->disabled(),
                    Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->disabled()
                        ->columnSpanFull(),
                    Forms\Components\DateTimePicker::make('created_at')
                        ->label('Signalé le')
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

                Tables\Columns\TextColumn::make('reporter.name')
                    ->label('Signalant')
                    ->searchable()
                    ->default('—'),

                Tables\Columns\TextColumn::make('booking_request_id')
                    ->label('Réservation')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Motif')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof ReportReason ? $state->value : $state)
                    ->color(fn ($state): string => match (true) {
                        $state === ReportReason::NoShow || $state === 'no_show'                              => 'danger',
                        $state === ReportReason::LateArrival || $state === 'late_arrival'                   => 'warning',
                        $state === ReportReason::QualityIssue || $state === 'quality_issue'                 => 'info',
                        $state === ReportReason::Payment || $state === 'payment_issue'                      => 'danger',
                        $state === ReportReason::Behaviour || $state === 'inappropriate_behaviour'          => 'danger',
                        default                                                                             => 'gray',
                    }),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(80)
                    ->tooltip(fn ($state) => $state),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'   => 'warning',
                        'reviewed'  => 'info',
                        'resolved'  => 'success',
                        'dismissed' => 'gray',
                        default     => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Signalé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending'   => 'En attente',
                        'reviewed'  => 'Examiné',
                        'resolved'  => 'Résolu',
                        'dismissed' => 'Rejeté',
                    ]),

                Tables\Filters\SelectFilter::make('reason')
                    ->label('Motif')
                    ->options(collect(ReportReason::cases())->mapWithKeys(
                        fn (ReportReason $r) => [$r->value => $r->value]
                    )->toArray()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('resolve')
                    ->label('Résoudre')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Résoudre ce signalement')
                    ->modalDescription('Le signalement sera marqué comme résolu.')
                    ->visible(fn (Report $record): bool => $record->status === 'pending' || $record->status === 'reviewed')
                    ->action(function (Report $record): void {
                        $record->update(['status' => 'resolved']);
                        Notification::make()->title('Signalement résolu')->success()->send();
                    }),

                Tables\Actions\Action::make('dismiss')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Rejeter ce signalement')
                    ->modalDescription('Le signalement sera marqué comme rejeté.')
                    ->visible(fn (Report $record): bool => $record->status === 'pending' || $record->status === 'reviewed')
                    ->action(function (Report $record): void {
                        $record->update(['status' => 'dismissed']);
                        Notification::make()->title('Signalement rejeté')->warning()->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('dismiss_bulk')
                        ->label('Rejeter la sélection')
                        ->icon('heroicon-o-x-mark')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $records->each(fn (Report $r) => $r->update(['status' => 'dismissed']));
                            Notification::make()->title('Signalements rejetés')->success()->send();
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReports::route('/'),
            'view'  => Pages\ViewReport::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['reporter', 'bookingRequest']);
    }
}
