<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoginLockoutLogResource\Pages;
use App\Models\LoginLockoutLog;
use App\Services\AuthService;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LoginLockoutLogResource extends Resource
{
    protected static ?string $model = LoginLockoutLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationLabel = 'Comptes bloqués';

    protected static ?string $modelLabel = 'Blocage';

    protected static ?string $pluralModelLabel = 'Comptes bloqués';

    protected static ?string $navigationGroup = 'Sécurité';

    protected static ?int $navigationSort = 25;

    protected static ?string $slug = 'login-lockouts';

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return ($user?->is_admin === true) || ($user?->hasAnyRole(['admin_ceo', 'admin_moderateur']) ?? false);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = LoginLockoutLog::whereNull('unlocked_at')
            ->where('locked_until', '>', now())
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->label('Email ciblé')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('user.full_name')
                    ->label('Utilisateur')
                    ->getStateUsing(fn (LoginLockoutLog $record): string => $record->user
                        ? $record->user->first_name . ' ' . $record->user->last_name
                        : '—')
                    ->placeholder('Compte inconnu'),

                Tables\Columns\TextColumn::make('client_type')
                    ->label('Canal')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'web'    => 'Web',
                        'mobile' => 'Mobile',
                        default  => 'API',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'web'    => 'info',
                        'mobile' => 'warning',
                        default  => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'web'    => 'heroicon-o-globe-alt',
                        'mobile' => 'heroicon-o-device-phone-mobile',
                        default  => 'heroicon-o-command-line',
                    }),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('Adresse IP')
                    ->copyable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('attempts_count')
                    ->label('Tentatives')
                    ->alignCenter()
                    ->badge()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('locked_at')
                    ->label('Bloqué le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('locked_until')
                    ->label('Jusqu\'au')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->getStateUsing(function (LoginLockoutLog $record): string {
                        if ($record->unlocked_at !== null) {
                            return 'Déverrouillé';
                        }
                        if ($record->locked_until->isFuture()) {
                            return 'Actif';
                        }

                        return 'Expiré';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Actif'        => 'danger',
                        'Déverrouillé' => 'success',
                        default        => 'gray',
                    }),

                Tables\Columns\TextColumn::make('unlockedBy.full_name')
                    ->label('Déverrouillé par')
                    ->getStateUsing(fn (LoginLockoutLog $record): string => $record->unlockedBy
                        ? $record->unlockedBy->first_name . ' ' . $record->unlockedBy->last_name
                        : '—')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('locked_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'active'    => 'Actifs seulement',
                        'expired'   => 'Expirés',
                        'unlocked'  => 'Déverrouillés manuellement',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'active'   => $query->whereNull('unlocked_at')->where('locked_until', '>', now()),
                            'expired'  => $query->whereNull('unlocked_at')->where('locked_until', '<=', now()),
                            'unlocked' => $query->whereNotNull('unlocked_at'),
                            default    => $query,
                        };
                    }),

                Tables\Filters\SelectFilter::make('client_type')
                    ->label('Canal')
                    ->options([
                        'web'    => 'Web',
                        'mobile' => 'Mobile',
                        'api'    => 'API',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('unlock')
                    ->label('Déverrouiller')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->visible(fn (LoginLockoutLog $record): bool => $record->isActive())
                    ->requiresConfirmation()
                    ->modalHeading('Déverrouiller ce compte ?')
                    ->modalDescription(fn (LoginLockoutLog $record): string => "Le compte associé à l'adresse {$record->email} sera immédiatement déverrouillé.")
                    ->modalSubmitActionLabel('Oui, déverrouiller')
                    ->action(function (LoginLockoutLog $record): void {
                        /** @var \App\Models\User $admin */
                        $admin = auth()->user();
                        app(AuthService::class)->unlockAccount($record->email, $admin->id);

                        Notification::make()
                            ->title('Compte déverrouillé')
                            ->body("Le blocage pour {$record->email} a été levé.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('view_user_agent')
                    ->label('User-Agent')
                    ->icon('heroicon-o-information-circle')
                    ->color('gray')
                    ->modalHeading('Détails du client')
                    ->modalContent(fn (LoginLockoutLog $record) => view(
                        'filament.modals.lockout-details',
                        ['record' => $record]
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fermer')
                    ->visible(fn (LoginLockoutLog $record): bool => $record->user_agent !== null),
            ])
            ->bulkActions([])
            ->poll('30s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoginLockoutLogs::route('/'),
        ];
    }
}
