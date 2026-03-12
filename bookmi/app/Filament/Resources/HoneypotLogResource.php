<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HoneypotLogResource\Pages;
use App\Models\BlockedIp;
use App\Models\HoneypotLog;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class HoneypotLogResource extends Resource
{
    protected static ?string $model = HoneypotLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-bug-ant';

    protected static ?string $navigationLabel = 'Honeypot / Bots';

    protected static ?string $navigationGroup = 'Sécurité';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'Tentative bot';

    protected static ?string $pluralModelLabel = 'Tentatives bots';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('ip')
                    ->label('Adresse IP')
                    ->searchable()
                    ->copyable()
                    ->url(fn (HoneypotLog $r) => "https://www.whois.com/whois/{$r->ip}", true)
                    ->badge()
                    ->color(fn (HoneypotLog $r) => $r->is_blocked ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('country')
                    ->label('Pays')
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('city')
                    ->label('Ville')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('user_agent')
                    ->label('User Agent / Navigateur')
                    ->limit(60)
                    ->tooltip(fn (HoneypotLog $r) => $r->user_agent)
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('honeypot_value')
                    ->label('Valeur honeypot')
                    ->limit(40)
                    ->tooltip(fn (HoneypotLog $r) => $r->honeypot_value)
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('referer')
                    ->label('Referer')
                    ->limit(40)
                    ->tooltip(fn (HoneypotLog $r) => $r->referer)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_blocked')
                    ->label('IP bloquée')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-exclamation')
                    ->falseIcon('heroicon-o-shield-check')
                    ->trueColor('danger')
                    ->falseColor('success'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('blocked')
                    ->label('IPs bloquées uniquement')
                    ->query(fn (Builder $query) => $query->where('is_blocked', true)),

                Tables\Filters\Filter::make('not_blocked')
                    ->label('IPs non bloquées')
                    ->query(fn (Builder $query) => $query->where('is_blocked', false)),
            ])
            ->actions([
                Tables\Actions\Action::make('block_ip')
                    ->label('Bloquer IP')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Bloquer cette adresse IP')
                    ->modalDescription(fn (HoneypotLog $r) => "Bloquer définitivement l'IP {$r->ip} ? Elle ne pourra plus accéder à l'application.")
                    ->visible(fn (HoneypotLog $r) => ! $r->is_blocked)
                    ->action(function (HoneypotLog $record) {
                        BlockedIp::firstOrCreate(
                            ['ip' => $record->ip],
                            ['reason' => 'Honeypot — bloqué manuellement', 'blocked_by' => auth()->id()]
                        );
                        HoneypotLog::where('ip', $record->ip)->update(['is_blocked' => true]);
                        Cache::forget("blocked_ip:{$record->ip}");

                        Notification::make()
                            ->title("IP {$record->ip} bloquée")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('unblock_ip')
                    ->label('Débloquer IP')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Débloquer cette adresse IP')
                    ->visible(fn (HoneypotLog $r) => $r->is_blocked)
                    ->action(function (HoneypotLog $record) {
                        BlockedIp::where('ip', $record->ip)->delete();
                        HoneypotLog::where('ip', $record->ip)->update(['is_blocked' => false]);
                        Cache::forget("blocked_ip:{$record->ip}");

                        Notification::make()
                            ->title("IP {$record->ip} débloquée")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->label('Supprimer'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_block')
                        ->label('Bloquer les IPs sélectionnées')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            foreach ($records as $record) {
                                /** @var HoneypotLog $record */
                                BlockedIp::firstOrCreate(
                                    ['ip' => $record->ip],
                                    ['reason' => 'Honeypot — blocage en masse', 'blocked_by' => auth()->id()]
                                );
                                Cache::forget("blocked_ip:{$record->ip}");
                            }
                            $ips = $records->pluck('ip')->unique()->values()->all();
                            HoneypotLog::whereIn('ip', $ips)->update(['is_blocked' => true]);

                            Notification::make()
                                ->title(count($ips) . ' IP(s) bloquée(s)')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make()->label('Supprimer la sélection'),
                ]),
            ])
            ->poll('30s'); // auto-refresh every 30 seconds
    }

    public static function getRelationManagers(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHoneypotLogs::route('/'),
        ];
    }
}
