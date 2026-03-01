<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromoCodeResource\Pages;
use App\Models\PromoCode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PromoCodeResource extends Resource
{
    protected static ?string $model = PromoCode::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Codes promo';

    protected static ?string $modelLabel = 'Code promo';

    protected static ?string $pluralModelLabel = 'Codes promo';

    protected static ?string $navigationGroup = 'Finances';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'promo-codes';

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return ($user?->is_admin === true) || ($user?->hasRole('admin_ceo') ?? false);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Code promo')
                ->schema([
                    Forms\Components\TextInput::make('code')
                        ->label('Code')
                        ->required()
                        ->maxLength(50)
                        ->placeholder('EX: PROMO2026')
                        ->helperText('Le code sera automatiquement mis en majuscules.')
                        ->dehydrateStateUsing(fn (string $state): string => strtoupper($state)),

                    Forms\Components\Select::make('type')
                        ->label('Type de réduction')
                        ->required()
                        ->options([
                            'percentage' => 'Pourcentage (%)',
                            'fixed'      => 'Montant fixe (XOF)',
                        ])
                        ->default('percentage')
                        ->live(),

                    Forms\Components\TextInput::make('value')
                        ->label(fn (Forms\Get $get): string => $get('type') === 'fixed' ? 'Montant (XOF)' : 'Pourcentage (%)')
                        ->required()
                        ->integer()
                        ->minValue(1)
                        ->maxValue(fn (Forms\Get $get): int => $get('type') === 'percentage' ? 100 : 99_999_999),

                    Forms\Components\TextInput::make('max_uses')
                        ->label('Nombre max d\'utilisations')
                        ->integer()
                        ->minValue(1)
                        ->nullable()
                        ->placeholder('Illimité si vide'),

                    Forms\Components\TextInput::make('min_booking_amount')
                        ->label('Montant minimum de réservation (XOF)')
                        ->integer()
                        ->minValue(0)
                        ->nullable()
                        ->placeholder('Aucun minimum si vide'),

                    Forms\Components\DateTimePicker::make('expires_at')
                        ->label('Date d\'expiration')
                        ->nullable()
                        ->placeholder('Pas d\'expiration'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Actif')
                        ->default(true),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'percentage' => 'Pourcentage',
                        'fixed'      => 'Montant fixe',
                        default      => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'percentage' => 'info',
                        'fixed'      => 'warning',
                        default      => 'gray',
                    }),

                Tables\Columns\TextColumn::make('value')
                    ->label('Valeur')
                    ->formatStateUsing(
                        fn (PromoCode $record): string => $record->type === 'percentage'
                        ? "{$record->value} %"
                        : number_format($record->value, 0, ',', ' ') . ' XOF'
                    ),

                Tables\Columns\TextColumn::make('usage')
                    ->label('Utilisations')
                    ->getStateUsing(
                        fn (PromoCode $record): string => $record->max_uses !== null
                        ? "{$record->used_count} / {$record->max_uses}"
                        : "{$record->used_count} / ∞"
                    ),

                Tables\Columns\TextColumn::make('min_booking_amount')
                    ->label('Montant min.')
                    ->formatStateUsing(
                        fn (?int $state): string => $state !== null
                        ? number_format($state, 0, ',', ' ') . ' XOF'
                        : '—'
                    ),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expiration')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Jamais'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif')
                    ->placeholder('Tous')
                    ->trueLabel('Actifs seulement')
                    ->falseLabel('Inactifs seulement'),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'percentage' => 'Pourcentage',
                        'fixed'      => 'Montant fixe',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Modifier'),

                Tables\Actions\Action::make('toggle_active')
                    ->label(fn (PromoCode $record): string => $record->is_active ? 'Désactiver' : 'Activer')
                    ->icon(fn (PromoCode $record): string => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (PromoCode $record): string => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function (PromoCode $record): void {
                        $record->update(['is_active' => ! $record->is_active]);
                        Notification::make()
                            ->title($record->is_active ? 'Code promo activé' : 'Code promo désactivé')
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
            'index'  => Pages\ListPromoCodes::route('/'),
            'create' => Pages\CreatePromoCode::route('/create'),
            'edit'   => Pages\EditPromoCode::route('/{record}/edit'),
        ];
    }
}
