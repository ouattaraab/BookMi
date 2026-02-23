<?php

namespace App\Filament\Resources;

use App\Enums\TalentLevel;
use App\Filament\Resources\TalentProfileResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TalentProfileResource extends Resource
{
    // Modèle User filtré par rôle 'talent' — affiche TOUS les talents
    // (avec ou sans profil créé, web + mobile).
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-musical-note';

    protected static ?string $navigationLabel = 'Profils talents';

    protected static ?string $modelLabel = 'Profil talent';

    protected static ?string $pluralModelLabel = 'Profils talents';

    protected static ?string $navigationGroup = 'Gestion des utilisateurs';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'talent-profiles';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->role('talent')
            ->with('talentProfile');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informations personnelles')
                ->schema([
                    Forms\Components\TextInput::make('first_name')
                        ->label('Prénom')
                        ->disabled(),
                    Forms\Components\TextInput::make('last_name')
                        ->label('Nom')
                        ->disabled(),
                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->disabled(),
                    Forms\Components\TextInput::make('phone')
                        ->label('Téléphone')
                        ->disabled(),
                ])->columns(2),

            Forms\Components\Section::make('Profil talent')
                ->schema([
                    Forms\Components\TextInput::make('talentProfile.stage_name')
                        ->label('Nom de scène')
                        ->disabled(),
                    Forms\Components\TextInput::make('talentProfile.city')
                        ->label('Ville')
                        ->disabled(),
                    Forms\Components\TextInput::make('talentProfile.talent_level')
                        ->label('Niveau')
                        ->disabled(),
                    Forms\Components\Toggle::make('talentProfile.is_verified')
                        ->label('Vérifié')
                        ->disabled(),
                    Forms\Components\TextInput::make('talentProfile.average_rating')
                        ->label('Note moyenne')
                        ->disabled(),
                    Forms\Components\TextInput::make('talentProfile.total_bookings')
                        ->label('Total réservations')
                        ->disabled(),
                    Forms\Components\TextInput::make('talentProfile.profile_completion_percentage')
                        ->label('Complétion du profil (%)')
                        ->disabled(),
                ])->columns(2),

            Forms\Components\Section::make('Statut')
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Actif')
                        ->disabled(),
                    Forms\Components\Toggle::make('is_suspended')
                        ->label('Suspendu')
                        ->disabled(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nom complet')
                    ->getStateUsing(fn (User $record): string => $record->first_name . ' ' . $record->last_name)
                    ->searchable(query: function ($query, string $search) {
                        $query->where(function ($q) use ($search) {
                            $q->where('first_name', 'like', "%{$search}%")
                              ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    }),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('talentProfile.stage_name')
                    ->label('Nom de scène')
                    ->searchable()
                    ->default('—'),

                Tables\Columns\TextColumn::make('talentProfile.city')
                    ->label('Ville')
                    ->searchable()
                    ->default('—'),

                Tables\Columns\BadgeColumn::make('talentProfile.talent_level')
                    ->label('Niveau')
                    ->formatStateUsing(fn ($state): string => match (true) {
                        $state === TalentLevel::NOUVEAU || $state === 'nouveau'     => 'Nouveau',
                        $state === TalentLevel::CONFIRME || $state === 'confirme'   => 'Confirmé',
                        $state === TalentLevel::POPULAIRE || $state === 'populaire' => 'Populaire',
                        $state === TalentLevel::ELITE || $state === 'elite'         => 'Élite',
                        default => '—',
                    })
                    ->color(fn ($state): string => match (true) {
                        $state === TalentLevel::NOUVEAU || $state === 'nouveau'     => 'gray',
                        $state === TalentLevel::CONFIRME || $state === 'confirme'   => 'info',
                        $state === TalentLevel::POPULAIRE || $state === 'populaire' => 'warning',
                        $state === TalentLevel::ELITE || $state === 'elite'         => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('talentProfile.is_verified')
                    ->label('Vérifié')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-shield-exclamation')
                    ->trueColor('success')
                    ->falseColor('warning'),

                Tables\Columns\TextColumn::make('talentProfile.average_rating')
                    ->label('Note moy.')
                    ->formatStateUsing(fn ($state): string => $state ? number_format((float) $state, 1) . ' / 5' : '—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('talentProfile.total_bookings')
                    ->label('Réservations')
                    ->formatStateUsing(fn ($state): string => $state !== null ? (string) $state : '0')
                    ->sortable(),

                Tables\Columns\TextColumn::make('talentProfile.profile_completion_percentage')
                    ->label('Complétion')
                    ->formatStateUsing(fn ($state): string => $state !== null ? $state . '%' : '—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Inscrit le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('verified')
                    ->label('Vérifié')
                    ->placeholder('Tous')
                    ->trueLabel('Vérifiés seulement')
                    ->falseLabel('Non vérifiés seulement')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('talentProfile', fn ($q) => $q->where('is_verified', true)),
                        false: fn (Builder $query) => $query->whereHas('talentProfile', fn ($q) => $q->where('is_verified', false)),
                        blank: fn (Builder $query) => $query,
                    ),

                Tables\Filters\SelectFilter::make('talent_level')
                    ->label('Niveau')
                    ->options([
                        'nouveau'   => 'Nouveau',
                        'confirme'  => 'Confirmé',
                        'populaire' => 'Populaire',
                        'elite'     => 'Élite',
                    ])
                    ->query(
                        fn (Builder $query, array $data): Builder =>
                        isset($data['value']) && $data['value'] !== ''
                            ? $query->whereHas('talentProfile', fn ($q) => $q->where('talent_level', $data['value']))
                            : $query
                    ),

                Tables\Filters\TernaryFilter::make('has_profile')
                    ->label('Profil créé')
                    ->placeholder('Tous')
                    ->trueLabel('Avec profil')
                    ->falseLabel('Sans profil')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('talentProfile'),
                        false: fn (Builder $query) => $query->whereDoesntHave('talentProfile'),
                        blank: fn (Builder $query) => $query,
                    ),
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
