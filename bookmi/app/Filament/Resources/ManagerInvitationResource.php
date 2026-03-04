<?php

namespace App\Filament\Resources;

use App\Enums\ManagerInvitationStatus;
use App\Filament\Resources\ManagerInvitationResource\Pages;
use App\Models\ManagerInvitation;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ManagerInvitationResource extends Resource
{
    protected static ?string $model = ManagerInvitation::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope-open';

    protected static ?string $navigationLabel = 'Invitations manager';

    protected static ?string $modelLabel = 'Invitation manager';

    protected static ?string $pluralModelLabel = 'Invitations manager';

    protected static ?string $navigationGroup = 'Activité';

    protected static ?int $navigationSort = 10;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return ($user?->is_admin === true) || ($user?->hasAnyRole(['admin_ceo', 'admin_moderateur']) ?? false);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('talentProfile.stage_name')
                    ->label('Talent')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('manager_email')
                    ->label('Email manager')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'warning' => ManagerInvitationStatus::Pending->value,
                        'success' => ManagerInvitationStatus::Accepted->value,
                        'danger'  => ManagerInvitationStatus::Rejected->value,
                    ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'pending'  => 'En attente',
                        'accepted' => 'Acceptée',
                        'rejected' => 'Refusée',
                        default    => $state,
                    }),

                Tables\Columns\TextColumn::make('invited_at')
                    ->label('Invité le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('responded_at')
                    ->label('Répondu le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('manager_comment')
                    ->label('Commentaire')
                    ->limit(60)
                    ->placeholder('—'),
            ])
            ->defaultSort('invited_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending'  => 'En attente',
                        'accepted' => 'Acceptée',
                        'rejected' => 'Refusée',
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListManagerInvitations::route('/'),
        ];
    }
}
