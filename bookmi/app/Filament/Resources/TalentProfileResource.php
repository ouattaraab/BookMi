<?php

namespace App\Filament\Resources;

use App\Enums\TalentLevel;
use App\Filament\Resources\TalentProfileResource\Pages;
use App\Models\TalentProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TalentProfileResource extends Resource
{
    protected static ?string $model = TalentProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-musical-note';

    protected static ?string $navigationLabel = 'Profils talents';

    protected static ?string $modelLabel = 'Profil talent';

    protected static ?string $pluralModelLabel = 'Profils talents';

    protected static ?string $navigationGroup = 'Gestion des utilisateurs';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identité')
                ->schema([
                    Forms\Components\TextInput::make('stage_name')
                        ->label('Nom de scène')
                        ->disabled(),
                    Forms\Components\TextInput::make('user.email')
                        ->label('Email utilisateur')
                        ->disabled(),
                    Forms\Components\TextInput::make('city')
                        ->label('Ville')
                        ->disabled(),
                    Forms\Components\TextInput::make('talent_level')
                        ->label('Niveau')
                        ->disabled(),
                ])->columns(2),

            Forms\Components\Section::make('Statistiques')
                ->schema([
                    Forms\Components\TextInput::make('average_rating')
                        ->label('Note moyenne')
                        ->disabled(),
                    Forms\Components\TextInput::make('total_bookings')
                        ->label('Total réservations')
                        ->disabled(),
                    Forms\Components\TextInput::make('profile_completion_percentage')
                        ->label('Complétion du profil (%)')
                        ->disabled(),
                    Forms\Components\Toggle::make('is_verified')
                        ->label('Vérifié')
                        ->disabled(),
                ])->columns(2),

            Forms\Components\Section::make('Biographie')
                ->schema([
                    Forms\Components\Textarea::make('bio')
                        ->label('Bio')
                        ->disabled()
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('stage_name')
                    ->label('Nom de scène')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('city')
                    ->label('Ville')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('talent_level')
                    ->label('Niveau')
                    ->formatStateUsing(fn ($state): string => match (true) {
                        $state === TalentLevel::NOUVEAU || $state === 'nouveau'     => 'Nouveau',
                        $state === TalentLevel::CONFIRME || $state === 'confirme'   => 'Confirmé',
                        $state === TalentLevel::POPULAIRE || $state === 'populaire' => 'Populaire',
                        $state === TalentLevel::ELITE || $state === 'elite'         => 'Élite',
                        default => (string) $state,
                    })
                    ->color(fn ($state): string => match (true) {
                        $state === TalentLevel::NOUVEAU || $state === 'nouveau'     => 'gray',
                        $state === TalentLevel::CONFIRME || $state === 'confirme'   => 'info',
                        $state === TalentLevel::POPULAIRE || $state === 'populaire' => 'warning',
                        $state === TalentLevel::ELITE || $state === 'elite'         => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('is_verified')
                    ->label('Vérifié')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-shield-exclamation')
                    ->trueColor('success')
                    ->falseColor('warning'),

                Tables\Columns\TextColumn::make('average_rating')
                    ->label('Note moy.')
                    ->formatStateUsing(fn ($state): string => $state ? number_format((float) $state, 1) . ' / 5' : '—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_bookings')
                    ->label('Réservations')
                    ->sortable(),

                Tables\Columns\TextColumn::make('profile_completion_percentage')
                    ->label('Complétion')
                    ->formatStateUsing(fn ($state): string => ($state ?? 0) . '%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('Vérifié')
                    ->placeholder('Tous')
                    ->trueLabel('Vérifiés seulement')
                    ->falseLabel('Non vérifiés seulement'),

                Tables\Filters\SelectFilter::make('talent_level')
                    ->label('Niveau')
                    ->options([
                        'nouveau'   => 'Nouveau',
                        'confirme'  => 'Confirmé',
                        'populaire' => 'Populaire',
                        'elite'     => 'Élite',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Voir'),
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
            'index' => Pages\ListTalentProfiles::route('/'),
            'view'  => Pages\ViewTalentProfile::route('/{record}'),
        ];
    }
}
