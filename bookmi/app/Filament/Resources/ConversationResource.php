<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConversationResource\Pages;
use App\Models\Conversation;
use App\Models\Message;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ConversationResource extends Resource
{
    protected static ?string $model = Conversation::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Conversations';

    protected static ?string $modelLabel = 'Conversation';

    protected static ?string $pluralModelLabel = 'Conversations';

    protected static ?string $navigationGroup = 'Modération';

    protected static ?int $navigationSort = 15;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return ($user?->is_admin === true) || ($user?->hasAnyRole(['admin_ceo', 'admin_moderation']) ?? false);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Conversation')
                ->schema([
                    Forms\Components\TextInput::make('client.name')
                        ->label('Client')
                        ->disabled(),
                    Forms\Components\TextInput::make('talentProfile.stage_name')
                        ->label('Talent')
                        ->disabled(),
                    Forms\Components\TextInput::make('booking_request_id')
                        ->label('Réservation #')
                        ->disabled(),
                    Forms\Components\DateTimePicker::make('last_message_at')
                        ->label('Dernier message le')
                        ->disabled(),
                    Forms\Components\DateTimePicker::make('created_at')
                        ->label('Créée le')
                        ->disabled(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('last_message_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->default('—'),

                Tables\Columns\TextColumn::make('talentProfile.stage_name')
                    ->label('Talent')
                    ->searchable()
                    ->default('—'),

                Tables\Columns\TextColumn::make('latestMessage.content')
                    ->label('Dernier message')
                    ->limit(60)
                    ->placeholder('Aucun message'),

                Tables\Columns\TextColumn::make('flagged_messages_count')
                    ->label('Signalés')
                    ->badge()
                    ->color(fn ($state): string => (int) $state > 0 ? 'danger' : 'gray')
                    ->default(0),

                Tables\Columns\TextColumn::make('last_message_at')
                    ->label('Dernier message')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_flagged_messages')
                    ->label('Avec messages signalés')
                    ->query(fn (Builder $query): Builder => $query->has('messages', '>=', 1, 'and', fn ($q) => $q->where('is_flagged', true))),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('view_flagged_messages')
                    ->label('Messages signalés')
                    ->icon('heroicon-o-flag')
                    ->color('danger')
                    ->visible(fn (Conversation $record): bool => (int) ($record->flagged_messages_count ?? 0) > 0)
                    ->modalHeading(fn (Conversation $record): string => "Messages signalés — Conversation #{$record->id}")
                    ->modalContent(function (Conversation $record): \Illuminate\Contracts\View\View {
                        $flaggedMessages = Message::where('conversation_id', $record->id)
                            ->where('is_flagged', true)
                            ->with('sender')
                            ->orderBy('created_at')
                            ->get();

                        return view('filament.partials.flagged-messages-modal', [
                            'messages' => $flaggedMessages,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fermer'),
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
            'index' => Pages\ListConversations::route('/'),
            'view'  => Pages\ViewConversation::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['client', 'talentProfile', 'latestMessage'])
            ->withCount(['messages as flagged_messages_count' => fn ($q) => $q->where('is_flagged', true)]);
    }
}
