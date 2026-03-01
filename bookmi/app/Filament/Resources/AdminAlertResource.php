<?php

namespace App\Filament\Resources;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use App\Filament\Resources\AdminAlertResource\Pages;
use App\Models\AdminAlert;
use App\Services\ActivityLogger;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry as InfolistTextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class AdminAlertResource extends Resource
{
    protected static ?string $model = AdminAlert::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return ($user?->is_admin ?? false) || ($user?->hasAnyRole(['admin_ceo', 'admin_moderateur']) ?? false);
    }

    protected static ?string $navigationLabel = 'Alertes';

    protected static ?string $modelLabel = 'Alerte';

    protected static ?string $pluralModelLabel = 'Alertes';

    protected static ?string $navigationGroup = 'Modération';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $count = AdminAlert::where('status', 'open')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informations')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Titre')
                        ->disabled(),
                    Forms\Components\TextInput::make('type')
                        ->label('Type')
                        ->disabled(),
                    Forms\Components\TextInput::make('severity')
                        ->label('Sévérité')
                        ->disabled(),
                    Forms\Components\TextInput::make('status')
                        ->label('Statut')
                        ->disabled(),
                    Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->disabled()
                        ->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make('Résolution')
                ->schema([
                    Forms\Components\DateTimePicker::make('resolved_at')
                        ->label('Résolu le')
                        ->disabled(),
                    Forms\Components\TextInput::make('resolvedBy.email')
                        ->label('Résolu par')
                        ->disabled(),
                ])->columns(2),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfolistSection::make('Informations')
                ->schema([
                    InfolistTextEntry::make('title')
                        ->label('Titre')
                        ->columnSpanFull(),
                    InfolistTextEntry::make('type')
                        ->label('Type')
                        ->badge()
                        ->formatStateUsing(fn ($state) => match (true) {
                            $state === AlertType::LowRating || $state === 'low_rating'                   => 'Note basse',
                            $state === AlertType::SuspiciousActivity || $state === 'suspicious_activity' => 'Activité suspecte',
                            $state === AlertType::PendingAction || $state === 'pending_action'           => 'Action en attente',
                            default => (string) $state,
                        })
                        ->color(fn ($state): string => match (true) {
                            $state === AlertType::LowRating || $state === 'low_rating'                   => 'warning',
                            $state === AlertType::SuspiciousActivity || $state === 'suspicious_activity' => 'danger',
                            $state === AlertType::PendingAction || $state === 'pending_action'           => 'info',
                            default => 'gray',
                        }),
                    InfolistTextEntry::make('severity')
                        ->label('Sévérité')
                        ->badge()
                        ->formatStateUsing(fn ($state) => match (true) {
                            $state === AlertSeverity::Critical || $state === 'critical' => 'Critique',
                            $state === AlertSeverity::Warning || $state === 'warning'   => 'Avertissement',
                            $state === AlertSeverity::Info || $state === 'info'         => 'Info',
                            default => (string) $state,
                        })
                        ->color(fn ($state): string => match (true) {
                            $state === AlertSeverity::Critical || $state === 'critical' => 'danger',
                            $state === AlertSeverity::Warning || $state === 'warning'   => 'warning',
                            $state === AlertSeverity::Info || $state === 'info'         => 'info',
                            default => 'gray',
                        }),
                    InfolistTextEntry::make('status')
                        ->label('Statut')
                        ->badge()
                        ->formatStateUsing(fn ($state) => match ($state) {
                            'open'      => 'Ouverte',
                            'resolved'  => 'Résolue',
                            'dismissed' => 'Ignorée',
                            default     => $state,
                        })
                        ->color(fn ($state): string => match ($state) {
                            'open'      => 'warning',
                            'resolved'  => 'success',
                            'dismissed' => 'gray',
                            default     => 'gray',
                        }),
                    InfolistTextEntry::make('description')
                        ->label('Description')
                        ->placeholder('Aucune description')
                        ->columnSpanFull(),
                ])->columns(2),

            InfolistSection::make('Résolution')
                ->schema([
                    InfolistTextEntry::make('resolved_at')
                        ->label('Résolu le')
                        ->dateTime('d/m/Y H:i')
                        ->placeholder('—'),
                    InfolistTextEntry::make('resolved_by_info')
                        ->label('Résolu par')
                        ->getStateUsing(fn ($record) => $record->resolvedBy
                            ? trim(($record->resolvedBy->first_name ?? '') . ' ' . ($record->resolvedBy->last_name ?? '')) . ' (' . $record->resolvedBy->email . ')'
                            : '—'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state): string => match (true) {
                        $state === AlertType::LowRating || $state === 'low_rating'                     => 'Note basse',
                        $state === AlertType::SuspiciousActivity || $state === 'suspicious_activity'   => 'Activité suspecte',
                        $state === AlertType::PendingAction || $state === 'pending_action'             => 'Action en attente',
                        default => (string) $state,
                    })
                    ->color(fn ($state): string => match (true) {
                        $state === AlertType::LowRating || $state === 'low_rating'                     => 'warning',
                        $state === AlertType::SuspiciousActivity || $state === 'suspicious_activity'   => 'danger',
                        $state === AlertType::PendingAction || $state === 'pending_action'             => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\BadgeColumn::make('severity')
                    ->label('Sévérité')
                    ->formatStateUsing(fn ($state): string => match (true) {
                        $state === AlertSeverity::Critical || $state === 'critical' => 'Critique',
                        $state === AlertSeverity::Warning || $state === 'warning'   => 'Avertissement',
                        $state === AlertSeverity::Info || $state === 'info'         => 'Info',
                        default => (string) $state,
                    })
                    ->color(fn ($state): string => match (true) {
                        $state === AlertSeverity::Critical || $state === 'critical' => 'danger',
                        $state === AlertSeverity::Warning || $state === 'warning'   => 'warning',
                        $state === AlertSeverity::Info || $state === 'info'         => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->color(fn ($state): string => match ($state) {
                        'open'      => 'warning',
                        'resolved'  => 'success',
                        'dismissed' => 'gray',
                        default     => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'open'      => 'Ouverte',
                        'resolved'  => 'Résolue',
                        'dismissed' => 'Ignorée',
                    ]),

                Tables\Filters\SelectFilter::make('severity')
                    ->label('Sévérité')
                    ->options([
                        'info'     => 'Info',
                        'warning'  => 'Avertissement',
                        'critical' => 'Critique',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Voir'),

                Tables\Actions\Action::make('resolve')
                    ->label('Résoudre')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (AdminAlert $record): bool => $record->status === 'open')
                    ->requiresConfirmation()
                    ->modalHeading('Marquer comme résolue ?')
                    ->action(function (AdminAlert $record): void {
                        $record->update([
                            'status'         => 'resolved',
                            'resolved_at'    => now(),
                            'resolved_by_id' => Auth::id(),
                        ]);

                        ActivityLogger::log('alert.resolved', $record, [
                            'title'    => $record->title,
                            'severity' => is_string($record->severity) ? $record->severity : $record->severity?->value,
                        ]);

                        Notification::make()
                            ->title('Alerte marquée comme résolue')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('dismiss')
                    ->label('Ignorer')
                    ->icon('heroicon-o-archive-box')
                    ->color('gray')
                    ->visible(fn (AdminAlert $record): bool => $record->status === 'open')
                    ->requiresConfirmation()
                    ->modalHeading('Ignorer cette alerte ?')
                    ->action(function (AdminAlert $record): void {
                        $record->update(['status' => 'dismissed']);

                        ActivityLogger::log('alert.dismissed', $record, [
                            'title' => $record->title,
                        ]);

                        Notification::make()
                            ->title('Alerte ignorée')
                            ->send();
                    }),
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
            'index' => Pages\ListAdminAlerts::route('/'),
            'view'  => Pages\ViewAdminAlert::route('/{record}'),
        ];
    }
}
