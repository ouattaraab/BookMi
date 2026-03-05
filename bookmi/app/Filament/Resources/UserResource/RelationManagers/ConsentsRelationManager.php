<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Enums\ConsentType;
use App\Services\ConsentService;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Response;

class ConsentsRelationManager extends RelationManager
{
    protected static string $relationship = 'consents';

    protected static ?string $title = 'Consentements';

    protected static ?string $modelLabel = 'Consentement';

    protected static ?string $pluralModelLabel = 'Consentements';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('consent_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof ConsentType ? $state->label() : (string) $state)
                    ->color(fn ($state): string => match (true) {
                        in_array($state, [ConsentType::CguPrivacy, ConsentType::DataProcessing, ConsentType::AgeMinimum], true) => 'danger',
                        in_array($state, ConsentType::optIn(), true) => 'info',
                        in_array($state, [ConsentType::TransactionPayment, ConsentType::TransactionCancellation], true) => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('status')
                    ->label('Statut')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('consented_at')
                    ->label('Accepté le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('withdrawn_at')
                    ->label('Retiré le')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('document_version')
                    ->label('Version')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('Adresse IP')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('device_id')
                    ->label('Device ID')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('status')
                    ->label('Statut')
                    ->placeholder('Tous')
                    ->trueLabel('Actifs seulement')
                    ->falseLabel('Retirés seulement'),

                Tables\Filters\SelectFilter::make('consent_type')
                    ->label('Type')
                    ->options(array_combine(
                        array_map(fn (ConsentType $t): string => $t->value, ConsentType::cases()),
                        array_map(fn (ConsentType $t): string => $t->label(), ConsentType::cases()),
                    )),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_activity')
                    ->label('Télécharger activité')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->action(function () {
                        /** @var \App\Models\User $user */
                        $user    = $this->getOwnerRecord();
                        $service = app(ConsentService::class);
                        $data    = $service->exportUserActivity($user);

                        $filename = "bookmi_activite_{$user->id}_" . now()->format('Y-m-d') . '.json';

                        return Response::streamDownload(
                            fn () => print json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                            $filename,
                            ['Content-Type' => 'application/json'],
                        );
                    }),
            ])
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50]);
    }
}
