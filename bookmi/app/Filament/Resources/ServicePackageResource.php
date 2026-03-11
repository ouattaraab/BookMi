<?php

namespace App\Filament\Resources;

use App\Enums\PackageType;
use App\Filament\Resources\ServicePackageResource\Pages;
use App\Models\ServicePackage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ServicePackageResource extends Resource
{
    protected static ?string $model = ServicePackage::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Packages Services';

    protected static ?string $modelLabel = 'Package service';

    protected static ?string $pluralModelLabel = 'Packages services';

    protected static ?string $navigationGroup = 'Talents';

    protected static ?int $navigationSort = 30;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return ($user?->is_admin === true) || ($user?->hasAnyRole(['admin_ceo', 'admin_moderation']) ?? false);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Package')
                ->schema([
                    Forms\Components\TextInput::make('talentProfile.stage_name')
                        ->label('Talent')
                        ->disabled(),
                    Forms\Components\TextInput::make('name')
                        ->label('Nom')
                        ->disabled(),
                    Forms\Components\Select::make('type')
                        ->label('Type')
                        ->options(collect(PackageType::cases())->mapWithKeys(
                            fn (PackageType $t) => [$t->value => $t->value]
                        )->toArray())
                        ->disabled(),
                    Forms\Components\TextInput::make('cachet_amount')
                        ->label('Cachet (XOF)')
                        ->disabled(),
                    Forms\Components\TextInput::make('duration_minutes')
                        ->label('Durée (minutes)')
                        ->disabled(),
                    Forms\Components\TextInput::make('delivery_days')
                        ->label('Délai de livraison (jours)')
                        ->disabled(),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Actif'),
                    Forms\Components\TextInput::make('sort_order')
                        ->label('Ordre d\'affichage')
                        ->integer(),
                    Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->columnSpanFull()
                        ->disabled(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('talentProfile.stage_name')
                    ->label('Talent')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof PackageType ? $state->value : $state)
                    ->color(fn ($state): string => match (true) {
                        $state === PackageType::Standard || $state === 'standard'   => 'primary',
                        $state === PackageType::Essentiel || $state === 'essentiel' => 'warning',
                        $state === PackageType::Premium || $state === 'premium'     => 'success',
                        $state === PackageType::Micro || $state === 'micro'         => 'info',
                        default                                                     => 'gray',
                    }),

                Tables\Columns\TextColumn::make('cachet_amount')
                    ->label('Cachet (XOF)')
                    ->numeric(thousandsSeparator: ' ')
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Durée (min)')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('delivery_days')
                    ->label('Livraison (j)')
                    ->placeholder('—'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Ordre')
                    ->numeric(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options(collect(PackageType::cases())->mapWithKeys(
                        fn (PackageType $t) => [$t->value => $t->value]
                    )->toArray()),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif')
                    ->placeholder('Tous')
                    ->trueLabel('Actifs seulement')
                    ->falseLabel('Inactifs seulement'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->label('Modifier'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('toggle_active')
                        ->label('Basculer actif/inactif')
                        ->icon('heroicon-o-arrows-right-left')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $records->each(fn (ServicePackage $pkg) => $pkg->update(['is_active' => ! $pkg->is_active]));
                            Notification::make()->title('Packages mis à jour')->success()->send();
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListServicePackages::route('/'),
            'view'   => Pages\ViewServicePackage::route('/{record}'),
            'edit'   => Pages\EditServicePackage::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['talentProfile']);
    }
}
