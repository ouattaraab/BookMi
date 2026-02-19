<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\ActivityLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = "Journal d'activité";

    protected static ?string $modelLabel = "Entrée de journal";

    protected static ?string $pluralModelLabel = "Journal d'activité";

    protected static ?string $navigationGroup = 'Modération';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informations')
                ->schema([
                    Forms\Components\TextInput::make('causer.email')
                        ->label('Utilisateur')
                        ->disabled(),
                    Forms\Components\TextInput::make('action')
                        ->label('Action')
                        ->disabled(),
                    Forms\Components\TextInput::make('subject_type')
                        ->label('Type de sujet')
                        ->disabled(),
                    Forms\Components\TextInput::make('subject_id')
                        ->label('ID du sujet')
                        ->disabled(),
                    Forms\Components\TextInput::make('ip_address')
                        ->label('Adresse IP')
                        ->disabled(),
                    Forms\Components\DateTimePicker::make('created_at')
                        ->label('Date')
                        ->disabled(),
                ])->columns(2),

            Forms\Components\Section::make('Métadonnées')
                ->schema([
                    Forms\Components\KeyValue::make('metadata')
                        ->label('Métadonnées')
                        ->disabled(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('causer.email')
                    ->label('Utilisateur')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Système'),

                Tables\Columns\TextColumn::make('action')
                    ->label('Action')
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Modèle')
                    ->formatStateUsing(fn ($state): string => $state ? class_basename($state) : '—')
                    ->searchable(),

                Tables\Columns\TextColumn::make('subject_id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('Adresse IP')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('today')
                    ->label("Aujourd'hui")
                    ->query(fn ($query) => $query->whereDate('created_at', today())),
            ])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageActivityLogs::route('/'),
        ];
    }
}
