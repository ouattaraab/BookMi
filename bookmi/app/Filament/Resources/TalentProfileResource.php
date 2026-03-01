<?php

namespace App\Filament\Resources;

use App\Enums\TalentLevel;
use App\Filament\Resources\TalentProfileResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TalentProfileResource extends Resource
{
    // Modèle User filtré par rôle 'talent' — affiche TOUS les talents
    // (avec ou sans profil créé, web + mobile).
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-musical-note';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['admin_ceo', 'admin_controleur', 'admin_moderateur']) ?? false;
    }

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
                    Forms\Components\Toggle::make('talentProfile.is_group')
                        ->label('Groupe / Collectif')
                        ->disabled(),
                    Forms\Components\TextInput::make('talentProfile.group_size')
                        ->label('Taille du groupe')
                        ->disabled(),
                    Forms\Components\TextInput::make('talentProfile.collective_name')
                        ->label('Nom du collectif')
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

            Forms\Components\Section::make('Compte de paiement')
                ->schema([
                    Forms\Components\TextInput::make('talentProfile.payout_method')
                        ->label('Méthode')
                        ->disabled(),
                    Forms\Components\TextInput::make('talentProfile.available_balance')
                        ->label('Solde disponible (XOF)')
                        ->disabled(),
                    Forms\Components\KeyValue::make('talentProfile.payout_details')
                        ->label('Coordonnées')
                        ->disabled(),
                    Forms\Components\DateTimePicker::make('talentProfile.payout_method_verified_at')
                        ->label('Validé le')
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

                Tables\Columns\IconColumn::make('talentProfile.is_group')
                    ->label('Groupe')
                    ->boolean()
                    ->trueIcon('heroicon-o-user-group')
                    ->falseIcon('heroicon-o-user')
                    ->trueColor('info')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

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

                Tables\Columns\TextColumn::make('talentProfile.payout_method')
                    ->label('Méthode paiement')
                    ->formatStateUsing(fn ($state) => $state ?? '—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('talentProfile.payout_method_verified_at')
                    ->label('Compte validé')
                    ->boolean()
                    ->getStateUsing(fn (User $record): bool => $record->talentProfile?->payout_method_verified_at !== null)
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('talentProfile.available_balance')
                    ->label('Solde (XOF)')
                    ->numeric(thousandsSeparator: ' ')
                    ->default('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

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

                Tables\Filters\TernaryFilter::make('payout_verified')
                    ->label('Compte de paiement')
                    ->placeholder('Tous')
                    ->trueLabel('Compte validé')
                    ->falseLabel('En attente de validation')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas(
                            'talentProfile',
                            fn ($q) => $q->whereNotNull('payout_method_verified_at')
                        ),
                        false: fn (Builder $query) => $query->whereHas(
                            'talentProfile',
                            fn ($q) => $q->whereNotNull('payout_method')->whereNull('payout_method_verified_at')
                        ),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->actions([
                // ── Valider le compte de paiement ───────────────────────────
                Tables\Actions\Action::make('verify_payout')
                    ->label('Valider compte')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Valider le compte de paiement')
                    ->modalDescription('Le talent pourra effectuer des demandes de reversement une fois son compte validé.')
                    ->visible(
                        fn (User $record): bool =>
                        $record->talentProfile !== null
                        && $record->talentProfile->payout_method !== null
                        && $record->talentProfile->payout_method_verified_at === null
                    )
                    ->action(function (User $record): void {
                        $profile = $record->talentProfile;
                        if (! $profile) {
                            return;
                        }

                        $profile->update([
                            'payout_method_verified_at' => now(),
                            'payout_method_verified_by' => Auth::id(),
                        ]);

                        // Notifier le talent par push
                        dispatch(new \App\Jobs\SendPushNotification(
                            userId: $record->id,
                            title:  'Compte validé',
                            body:   'Votre compte de paiement a été validé. Vous pouvez maintenant demander un reversement.',
                            data:   ['type' => 'payout_verified'],
                        ));

                        Notification::make()
                            ->title('Compte de paiement validé')
                            ->success()
                            ->send();
                    }),

                // ── Invalider le compte de paiement ─────────────────────────
                Tables\Actions\Action::make('invalidate_payout')
                    ->label('Invalider compte')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Invalider le compte de paiement')
                    ->modalDescription('Le talent devra re-soumettre et faire valider son compte avant de demander un reversement.')
                    ->visible(
                        fn (User $record): bool =>
                        $record->talentProfile?->payout_method_verified_at !== null
                    )
                    ->action(function (User $record): void {
                        $record->talentProfile?->update([
                            'payout_method_verified_at' => null,
                            'payout_method_verified_by' => null,
                        ]);

                        Notification::make()
                            ->title('Compte de paiement invalidé')
                            ->warning()
                            ->send();
                    }),

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
