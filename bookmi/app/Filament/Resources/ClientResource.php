<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClientResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Clients';

    protected static ?string $modelLabel = 'Client';

    protected static ?string $pluralModelLabel = 'Clients';

    protected static ?string $navigationGroup = 'Gestion des utilisateurs';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'app-clients';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->role('client');
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

            Forms\Components\Section::make('Statut')
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Actif')
                        ->disabled(),
                    Forms\Components\Toggle::make('is_suspended')
                        ->label('Suspendu')
                        ->disabled(),
                    Forms\Components\DateTimePicker::make('suspended_at')
                        ->label('Suspendu le')
                        ->disabled(),
                    Forms\Components\Textarea::make('suspension_reason')
                        ->label('Motif de suspension')
                        ->disabled()
                        ->columnSpanFull(),
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
                    })
                    ->sortable(query: fn ($query, string $direction) => $query->orderBy('last_name', $direction)),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Téléphone')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('suspended_status')
                    ->label('Statut')
                    ->getStateUsing(fn (User $record): string => $record->is_suspended ? 'Suspendu' : 'Actif')
                    ->color(fn (string $state): string => match ($state) {
                        'Suspendu' => 'danger',
                        default    => 'success',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Inscrit le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_suspended')
                    ->label('Suspendu')
                    ->placeholder('Tous')
                    ->trueLabel('Suspendus seulement')
                    ->falseLabel('Actifs seulement'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Voir'),

                Tables\Actions\Action::make('toggle_suspend')
                    ->label(fn (User $record): string => $record->is_suspended ? 'Réactiver' : 'Suspendre')
                    ->icon(fn (User $record): string => $record->is_suspended ? 'heroicon-o-check-circle' : 'heroicon-o-no-symbol')
                    ->color(fn (User $record): string => $record->is_suspended ? 'success' : 'danger')
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record): string => $record->is_suspended ? 'Réactiver ce client ?' : 'Suspendre ce client ?')
                    ->modalDescription(
                        fn (User $record): string => $record->is_suspended
                        ? 'Le client pourra de nouveau se connecter et utiliser l\'application.'
                        : 'Le client ne pourra plus se connecter ni utiliser l\'application.'
                    )
                    ->form(fn (User $record): array => $record->is_suspended ? [] : [
                        Forms\Components\Textarea::make('suspension_reason')
                            ->label('Motif de suspension')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (User $record, array $data): void {
                        if ($record->is_suspended) {
                            $record->update([
                                'is_suspended'      => false,
                                'is_active'         => true,
                                'suspended_at'      => null,
                                'suspension_reason' => null,
                            ]);
                            Notification::make()
                                ->title('Client réactivé')
                                ->success()
                                ->send();
                        } else {
                            $record->update([
                                'is_suspended'      => true,
                                'is_active'         => false,
                                'suspended_at'      => now(),
                                'suspension_reason' => $data['suspension_reason'] ?? null,
                            ]);
                            Notification::make()
                                ->title('Client suspendu')
                                ->warning()
                                ->send();
                        }
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
            'index' => Pages\ListClients::route('/'),
            'view'  => Pages\ViewClient::route('/{record}'),
        ];
    }
}
