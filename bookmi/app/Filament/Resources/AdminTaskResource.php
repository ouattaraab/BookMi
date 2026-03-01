<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminTaskResource\Pages;
use App\Models\AdminTask;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AdminTaskResource extends Resource
{
    protected static ?string $model = AdminTask::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Tâches équipe';

    protected static ?string $modelLabel = 'Tâche';

    protected static ?string $pluralModelLabel = 'Tâches';

    protected static ?string $navigationGroup = 'Opérations';

    protected static ?int $navigationSort = 15;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return ($user?->is_admin === true) || ($user?->hasAnyRole([
            'admin_ceo',
            'admin_controleur',
            'admin_comptable',
            'admin_moderateur',
        ]) ?? false);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Tâche')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Titre')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Assignation')
                ->schema([
                    Forms\Components\Select::make('assigned_to')
                        ->label('Assigné à')
                        ->required()
                        ->options(
                            User::where('is_admin', true)
                                ->get()
                                ->mapWithKeys(fn (User $u) => [
                                    $u->id => $u->first_name . ' ' . $u->last_name . ' (' . $u->email . ')',
                                ])
                        )
                        ->searchable(),
                    Forms\Components\Select::make('priority')
                        ->label('Priorité')
                        ->options([
                            'low'    => 'Basse',
                            'normal' => 'Normale',
                            'high'   => 'Haute',
                        ])
                        ->default('normal')
                        ->required(),
                    Forms\Components\DatePicker::make('deadline')
                        ->label('Échéance')
                        ->native(false),
                    Forms\Components\Select::make('booking_request_id')
                        ->label('Réservation liée (optionnel)')
                        ->relationship('bookingRequest', 'id')
                        ->searchable()
                        ->nullable(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Tâche')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('assignedTo.first_name')
                    ->label('Assigné à')
                    ->getStateUsing(
                        fn (AdminTask $record): string =>
                        $record->assignedTo?->first_name . ' ' . $record->assignedTo?->last_name
                    )
                    ->searchable(
                        query: fn ($query, string $search) =>
                        $query->whereHas(
                            'assignedTo',
                            fn ($q) =>
                            $q->where('first_name', 'like', "%{$search}%")
                              ->orWhere('last_name', 'like', "%{$search}%")
                        )
                    ),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->getStateUsing(fn (AdminTask $record): string => match ($record->status) {
                        'pending'     => 'En attente',
                        'in_progress' => 'En cours',
                        'completed'   => 'Terminée',
                        default       => $record->status,
                    })
                    ->color(fn (AdminTask $record): string => match ($record->status) {
                        'pending'     => 'warning',
                        'in_progress' => 'info',
                        'completed'   => 'success',
                        default       => 'gray',
                    }),

                Tables\Columns\BadgeColumn::make('priority')
                    ->label('Priorité')
                    ->getStateUsing(fn (AdminTask $record): string => match ($record->priority) {
                        'low'    => 'Basse',
                        'normal' => 'Normale',
                        'high'   => 'Haute',
                        default  => $record->priority,
                    })
                    ->color(fn (AdminTask $record): string => match ($record->priority) {
                        'low'    => 'gray',
                        'normal' => 'primary',
                        'high'   => 'danger',
                        default  => 'gray',
                    }),

                Tables\Columns\TextColumn::make('deadline')
                    ->label('Échéance')
                    ->date('d/m/Y')
                    ->color(
                        fn (AdminTask $record): string =>
                        $record->isOverdue() ? 'danger' : 'gray'
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending'     => 'En attente',
                        'in_progress' => 'En cours',
                        'completed'   => 'Terminée',
                    ]),
                Tables\Filters\SelectFilter::make('priority')
                    ->label('Priorité')
                    ->options([
                        'low'    => 'Basse',
                        'normal' => 'Normale',
                        'high'   => 'Haute',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('mark_in_progress')
                    ->label('En cours')
                    ->icon('heroicon-o-play')
                    ->color('info')
                    ->visible(fn (AdminTask $record): bool => $record->status === 'pending')
                    ->action(function (AdminTask $record): void {
                        $record->update(['status' => 'in_progress']);
                        Notification::make()->title('Tâche démarrée')->info()->send();
                    }),

                Tables\Actions\Action::make('mark_completed')
                    ->label('Terminer')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (AdminTask $record): bool => $record->status !== 'completed')
                    ->requiresConfirmation()
                    ->action(function (AdminTask $record): void {
                        $record->update([
                            'status'       => 'completed',
                            'completed_at' => now(),
                        ]);
                        Notification::make()->title('Tâche terminée')->success()->send();
                    }),

                Tables\Actions\EditAction::make()->label('Éditer'),
                Tables\Actions\DeleteAction::make()->label('Supprimer'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAdminTasks::route('/'),
            'create' => Pages\CreateAdminTask::route('/create'),
            'edit'   => Pages\EditAdminTask::route('/{record}/edit'),
        ];
    }
}
