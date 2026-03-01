<?php

namespace App\Filament\Resources;

use App\Enums\WarningStatus;
use App\Filament\Resources\AdminWarningResource\Pages;
use App\Models\AdminWarning;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry as InfolistTextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AdminWarningResource extends Resource
{
    protected static ?string $model = AdminWarning::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['admin_ceo', 'admin_moderateur']) ?? false;
    }

    protected static ?string $navigationLabel = 'Avertissements';

    protected static ?string $modelLabel = 'Avertissement';

    protected static ?string $pluralModelLabel = 'Avertissements';

    protected static ?string $navigationGroup = 'Modération';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $count = AdminWarning::where('status', 'active')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Utilisateur concerné')
                ->schema([
                    Forms\Components\TextInput::make('user.email')
                        ->label('Email utilisateur')
                        ->disabled(),
                    Forms\Components\TextInput::make('issuedBy.email')
                        ->label('Émis par')
                        ->disabled(),
                ])->columns(2),

            Forms\Components\Section::make('Détails')
                ->schema([
                    Forms\Components\TextInput::make('reason')
                        ->label('Motif')
                        ->disabled(),
                    Forms\Components\TextInput::make('status')
                        ->label('Statut')
                        ->disabled(),
                    Forms\Components\Textarea::make('details')
                        ->label('Détails')
                        ->disabled()
                        ->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make('Résolution')
                ->schema([
                    Forms\Components\DateTimePicker::make('resolved_at')
                        ->label('Résolu le')
                        ->disabled(),
                ])->columns(2),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfolistSection::make('Utilisateur concerné')
                ->schema([
                    InfolistTextEntry::make('user.email')
                        ->label('Email utilisateur'),
                    InfolistTextEntry::make('user_nom')
                        ->label('Nom complet')
                        ->getStateUsing(fn ($record) => trim(($record->user?->first_name ?? '') . ' ' . ($record->user?->last_name ?? '')) ?: '—'),
                    InfolistTextEntry::make('issued_by_info')
                        ->label('Émis par')
                        ->getStateUsing(fn ($record) => $record->issuedBy
                            ? trim(($record->issuedBy->first_name ?? '') . ' ' . ($record->issuedBy->last_name ?? '')) . ' (' . $record->issuedBy->email . ')'
                            : '—'),
                    InfolistTextEntry::make('created_at')
                        ->label('Émis le')
                        ->dateTime('d/m/Y H:i'),
                ])->columns(2),

            InfolistSection::make('Détails')
                ->schema([
                    InfolistTextEntry::make('reason')
                        ->label('Motif'),
                    InfolistTextEntry::make('status')
                        ->label('Statut')
                        ->badge()
                        ->formatStateUsing(fn ($state) => match (true) {
                            $state === WarningStatus::Active || $state === 'active'     => 'Actif',
                            $state === WarningStatus::Resolved || $state === 'resolved' => 'Résolu',
                            default => (string) $state,
                        })
                        ->color(fn ($state): string => match (true) {
                            $state === WarningStatus::Active || $state === 'active'     => 'warning',
                            $state === WarningStatus::Resolved || $state === 'resolved' => 'success',
                            default => 'gray',
                        }),
                    InfolistTextEntry::make('details')
                        ->label('Détails')
                        ->placeholder('Aucun')
                        ->columnSpanFull(),
                ])->columns(2),

            InfolistSection::make('Résolution')
                ->schema([
                    InfolistTextEntry::make('resolved_at')
                        ->label('Résolu le')
                        ->dateTime('d/m/Y H:i')
                        ->placeholder('—'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Utilisateur')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Motif')
                    ->limit(50)
                    ->tooltip(fn ($state) => $state),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn ($state): string => match (true) {
                        $state === WarningStatus::Active || $state === 'active'     => 'Actif',
                        $state === WarningStatus::Resolved || $state === 'resolved' => 'Résolu',
                        default => (string) $state,
                    })
                    ->color(fn ($state): string => match (true) {
                        $state === WarningStatus::Active || $state === 'active'     => 'warning',
                        $state === WarningStatus::Resolved || $state === 'resolved' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Émis le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('issuedBy.email')
                    ->label('Émis par')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'active'   => 'Actif',
                        'resolved' => 'Résolu',
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
            'index' => Pages\ListAdminWarnings::route('/'),
            'view'  => Pages\ViewAdminWarning::route('/{record}'),
        ];
    }
}
