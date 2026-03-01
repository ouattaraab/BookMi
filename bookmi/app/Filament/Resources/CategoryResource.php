<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return ($user?->is_admin === true) || ($user?->hasRole('admin_ceo') ?? false);
    }

    protected static ?string $navigationLabel = 'Catégories';

    protected static ?string $modelLabel = 'Catégorie';

    protected static ?string $pluralModelLabel = 'Catégories';

    protected static ?string $navigationGroup = 'Contenu';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informations de la catégorie')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nom')
                        ->required()
                        ->maxLength(100)
                        ->live(onBlur: true)
                        ->placeholder('Ex : DJ, Musicien, Danseur…'),

                    Forms\Components\Select::make('parent_id')
                        ->label('Catégorie parente')
                        ->options(fn () => Category::whereNull('parent_id')->orderBy('name')->pluck('name', 'id'))
                        ->nullable()
                        ->placeholder('Aucune (catégorie principale)'),

                    Forms\Components\TextInput::make('icon_path')
                        ->label('Icône (chemin ou classe)')
                        ->maxLength(255)
                        ->nullable()
                        ->placeholder('Ex : heroicon-o-musical-note'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->copyable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Parente')
                    ->default('—')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('talent_count')
                    ->label('Talents')
                    ->getStateUsing(fn (Category $record): int => $record->talentProfiles()->count())
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Modifier'),

                Tables\Actions\DeleteAction::make()
                    ->label('Supprimer')
                    ->before(function (Category $record, Tables\Actions\DeleteAction $action): void {
                        $count = \App\Models\TalentProfile::where('category_id', $record->id)->count();
                        if ($count > 0) {
                            Notification::make()
                                ->title('Suppression impossible')
                                ->body("Cette catégorie est utilisée par {$count} talent(s). Réaffectez-les d'abord.")
                                ->danger()
                                ->send();
                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Supprimer la sélection'),
                ]),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit'   => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
