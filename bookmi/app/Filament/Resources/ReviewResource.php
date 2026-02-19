<?php

namespace App\Filament\Resources;

use App\Enums\ReviewType;
use App\Filament\Resources\ReviewResource\Pages;
use App\Models\Review;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = 'Avis';

    protected static ?string $modelLabel = 'Avis';

    protected static ?string $pluralModelLabel = 'Avis';

    protected static ?string $navigationGroup = 'Activité';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Parties')
                ->schema([
                    Forms\Components\TextInput::make('bookingRequest.id')
                        ->label('Réservation #')
                        ->disabled(),
                    Forms\Components\TextInput::make('reviewer.email')
                        ->label('Auteur')
                        ->disabled(),
                    Forms\Components\TextInput::make('reviewee.email')
                        ->label('Destinataire')
                        ->disabled(),
                    Forms\Components\TextInput::make('type')
                        ->label('Type')
                        ->disabled(),
                ])->columns(2),

            Forms\Components\Section::make('Contenu')
                ->schema([
                    Forms\Components\TextInput::make('rating')
                        ->label('Note /5')
                        ->disabled(),
                    Forms\Components\Textarea::make('comment')
                        ->label('Commentaire')
                        ->disabled()
                        ->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make('Signalement')
                ->schema([
                    Forms\Components\Toggle::make('is_reported')
                        ->label('Signalé')
                        ->disabled(),
                    Forms\Components\DateTimePicker::make('reported_at')
                        ->label('Signalé le')
                        ->disabled(),
                    Forms\Components\Textarea::make('report_reason')
                        ->label('Motif du signalement')
                        ->disabled()
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('booking_request_id')
                    ->label('Réservation #')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reviewer.email')
                    ->label('Auteur')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('reviewee.email')
                    ->label('Destinataire')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state): string => match (true) {
                        $state === ReviewType::ClientToTalent || $state === 'client_to_talent' => 'Client → Talent',
                        $state === ReviewType::TalentToClient || $state === 'talent_to_client' => 'Talent → Client',
                        default => (string) $state,
                    })
                    ->color(fn ($state): string => match (true) {
                        $state === ReviewType::ClientToTalent || $state === 'client_to_talent' => 'info',
                        $state === ReviewType::TalentToClient || $state === 'talent_to_client' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('rating')
                    ->label('Note')
                    ->formatStateUsing(fn (int $state): string => str_repeat('★', $state) . str_repeat('☆', 5 - $state))
                    ->html()
                    ->sortable(),

                Tables\Columns\TextColumn::make('comment')
                    ->label('Commentaire')
                    ->limit(60)
                    ->tooltip(fn ($state) => $state),

                Tables\Columns\IconColumn::make('is_reported')
                    ->label('Signalé')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('gray')
                    ->trueIcon('heroicon-o-flag')
                    ->falseIcon('heroicon-o-minus'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_reported')
                    ->label('Signalé')
                    ->placeholder('Tous')
                    ->trueLabel('Signalés seulement')
                    ->falseLabel('Non signalés seulement')
                    ->default(true),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'client_to_talent' => 'Client → Talent',
                        'talent_to_client' => 'Talent → Client',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Voir'),

                Tables\Actions\Action::make('approve')
                    ->label('Valider')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Review $record): bool => $record->is_reported)
                    ->requiresConfirmation()
                    ->modalHeading('Valider cet avis ?')
                    ->modalDescription('Le signalement sera supprimé et l\'avis sera maintenu.')
                    ->action(function (Review $record): void {
                        $record->update([
                            'is_reported'   => false,
                            'report_reason' => null,
                            'reported_at'   => null,
                        ]);
                        Notification::make()
                            ->title('Avis validé — signalement retiré')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->label('Supprimer'),
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
            'index' => Pages\ListReviews::route('/'),
            'view'  => Pages\ViewReview::route('/{record}'),
        ];
    }
}
