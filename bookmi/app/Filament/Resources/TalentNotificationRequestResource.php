<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TalentNotificationRequestResource\Pages;
use App\Models\TalentNotificationRequest;
use App\Models\TalentProfile;
use App\Notifications\TalentAvailableNotification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Notification as LaravelNotification;

class TalentNotificationRequestResource extends Resource
{
    protected static ?string $model = TalentNotificationRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return ($user?->is_admin === true) || ($user?->hasRole('admin_ceo') ?? false);
    }

    protected static ?string $navigationLabel = 'Alertes disponibilité';

    protected static ?string $modelLabel = 'Alerte disponibilité';

    protected static ?string $pluralModelLabel = 'Alertes disponibilité';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $count = TalentNotificationRequest::whereNull('notified_at')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Demande de notification')->schema([
                Forms\Components\TextInput::make('search_query')
                    ->label('Artiste recherché')
                    ->disabled(),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->disabled(),
                Forms\Components\TextInput::make('phone')
                    ->label('Téléphone')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('notified_at')
                    ->label('Notifié le')
                    ->disabled(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('search_query')
                    ->label('Artiste recherché')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Téléphone')
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->getStateUsing(fn (TalentNotificationRequest $r): string => $r->notified_at ? 'Notifié' : 'En attente')
                    ->color(fn (string $state): string => $state === 'Notifié' ? 'success' : 'warning'),

                Tables\Columns\TextColumn::make('notified_at')
                    ->label('Notifié le')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Demande le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('notified_at')
                    ->label('Statut')
                    ->placeholder('Tous')
                    ->trueLabel('Notifiés')
                    ->falseLabel('En attente')
                    ->nullable(),
            ])
            ->actions([
                Tables\Actions\Action::make('send_notification')
                    ->label('Envoyer notification')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->visible(fn (TalentNotificationRequest $r): bool => $r->isPending() && $r->email !== null)
                    ->form([
                        Forms\Components\Select::make('talent_profile_id')
                            ->label('Talent à notifier')
                            ->options(fn () => TalentProfile::orderBy('stage_name')->pluck('stage_name', 'id'))
                            ->searchable()
                            ->required()
                            ->placeholder('Choisir un talent…'),
                    ])
                    ->action(function (TalentNotificationRequest $record, array $data): void {
                        $talent = TalentProfile::find($data['talent_profile_id']);
                        if (! $talent) {
                            Notification::make()->title('Talent introuvable')->danger()->send();
                            return;
                        }

                        LaravelNotification::route('mail', $record->email)
                            ->notify(new TalentAvailableNotification($talent, $record));

                        $record->update(['notified_at' => now()]);

                        Notification::make()
                            ->title('Email envoyé à ' . $record->email)
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('mark_notified')
                    ->label('Marquer notifié')
                    ->icon('heroicon-o-check-circle')
                    ->color('gray')
                    ->visible(fn (TalentNotificationRequest $r): bool => $r->isPending())
                    ->requiresConfirmation()
                    ->action(function (TalentNotificationRequest $record): void {
                        $record->update(['notified_at' => now()]);
                        Notification::make()->title('Marqué comme notifié')->success()->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('mark_all_notified')
                    ->label('Marquer comme notifiés')
                    ->icon('heroicon-o-check-circle')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each->update(['notified_at' => now()])),

                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTalentNotificationRequests::route('/'),
        ];
    }
}
