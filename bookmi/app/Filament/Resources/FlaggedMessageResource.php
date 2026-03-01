<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FlaggedMessageResource\Pages;
use App\Models\Message;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FlaggedMessageResource extends Resource
{
    protected static ?string $model = Message::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationLabel = 'Messages signalés';

    protected static ?string $navigationGroup = 'Modération';

    protected static ?int $navigationSort = 25;

    protected static ?string $modelLabel = 'Message signalé';

    protected static ?string $pluralModelLabel = 'Messages signalés';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['admin_ceo', 'admin_moderateur']) ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Message::where('is_flagged', true)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('is_flagged', true)
            ->with(['sender', 'conversation.client', 'conversation.talentProfile.user', 'conversation.bookingRequest'])
            ->latest();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sender.name')
                    ->label('Expéditeur')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('content')
                    ->label('Contenu')
                    ->limit(80)
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('conversation.client.name')
                    ->label('Client')
                    ->searchable(),
                Tables\Columns\TextColumn::make('conversation.talentProfile.stage_name')
                    ->label('Talent'),
                Tables\Columns\TextColumn::make('conversation.bookingRequest.id')
                    ->label('Réservation')
                    ->prefix('#')
                    ->placeholder('—'),
                Tables\Columns\IconColumn::make('is_flagged')
                    ->label('Signalé')
                    ->boolean()
                    ->trueColor('danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('today')
                    ->label("Aujourd'hui")
                    ->query(fn ($q) => $q->whereDate('created_at', today())),
                Tables\Filters\Filter::make('this_week')
                    ->label('Cette semaine')
                    ->query(fn ($q) => $q->where('created_at', '>=', now()->startOfWeek())),
            ])
            ->actions([
                Tables\Actions\Action::make('suspend_sender')
                    ->label('Suspendre')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Suspendre cet utilisateur empêchera tout accès à la plateforme.')
                    ->action(function (Message $record): void {
                        if ($record->sender && ! $record->sender->is_admin) {
                            $record->sender->update(['is_active' => false]);
                            Notification::make()
                                ->title('Utilisateur suspendu')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Utilisateur introuvable ou protégé.')
                                ->warning()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('unflag')
                    ->label('Ignorer')
                    ->icon('heroicon-o-check')
                    ->color('gray')
                    ->action(function (Message $record): void {
                        $record->update(['is_flagged' => false]);
                        Notification::make()
                            ->title('Message ignoré')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('unflag_selected')
                    ->label('Ignorer la sélection')
                    ->icon('heroicon-o-check')
                    ->action(fn ($records) => $records->each->update(['is_flagged' => false]))
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFlaggedMessages::route('/'),
        ];
    }
}
