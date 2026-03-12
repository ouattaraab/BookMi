<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SecurityEventResource\Pages;
use App\Models\BlockedIp;
use App\Models\SecurityEvent;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class SecurityEventResource extends Resource
{
    protected static ?string $model = SecurityEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationLabel = 'Événements sécurité';

    protected static ?string $navigationGroup = 'Sécurité';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Événement';

    protected static ?string $pluralModelLabel = 'Événements sécurité';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->since()
                    ->tooltip(fn (SecurityEvent $r) => $r->created_at->format('d/m/Y H:i:s')),

                Tables\Columns\BadgeColumn::make('severity')
                    ->label('Sévérité')
                    ->color(fn (string $state) => match ($state) {
                        'low'      => 'success',
                        'medium'   => 'warning',
                        'high'     => 'danger',
                        'critical' => 'danger',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'low'      => 'Faible',
                        'medium'   => 'Moyen',
                        'high'     => 'Élevé',
                        'critical' => 'Critique',
                        default    => $state,
                    })
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->color(fn (string $state) => match ($state) {
                        'login_failed'    => 'warning',
                        'login_locked'    => 'danger',
                        'honeypot_hit'    => 'danger',
                        'rate_limit'      => 'warning',
                        'blocked_attempt' => 'danger',
                        'suspicious_404'  => 'gray',
                        default           => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'login_failed'    => '🔑 Login échoué',
                        'login_locked'    => '🔒 Compte bloqué',
                        'honeypot_hit'    => '🍯 Honeypot',
                        'rate_limit'      => '⏱ Rate limit',
                        'blocked_attempt' => '🚫 IP bloquée',
                        'suspicious_404'  => '🔍 404 suspect',
                        default           => $state,
                    }),

                Tables\Columns\TextColumn::make('ip')
                    ->label('Adresse IP')
                    ->searchable()
                    ->copyable()
                    ->url(fn (SecurityEvent $r) => $r->ip ? "https://www.whois.com/whois/{$r->ip}" : null, true)
                    ->badge()
                    ->color(fn (SecurityEvent $r) => $r->ip_blocked ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('country')
                    ->label('Pays')
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('city')
                    ->label('Ville')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email ciblé')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('url')
                    ->label('URL')
                    ->limit(40)
                    ->tooltip(fn (SecurityEvent $r) => $r->url)
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('user_agent')
                    ->label('User Agent')
                    ->limit(50)
                    ->tooltip(fn (SecurityEvent $r) => $r->user_agent)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('ip_blocked')
                    ->label('IP bloquée')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-exclamation')
                    ->falseIcon('heroicon-o-shield-check')
                    ->trueColor('danger')
                    ->falseColor('success'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'login_failed'    => '🔑 Login échoué',
                        'login_locked'    => '🔒 Compte bloqué',
                        'honeypot_hit'    => '🍯 Honeypot',
                        'rate_limit'      => '⏱ Rate limit',
                        'blocked_attempt' => '🚫 IP bloquée',
                        'suspicious_404'  => '🔍 404 suspect',
                    ]),

                Tables\Filters\SelectFilter::make('severity')
                    ->label('Sévérité')
                    ->options([
                        'low'      => 'Faible',
                        'medium'   => 'Moyen',
                        'high'     => 'Élevé',
                        'critical' => 'Critique',
                    ]),

                Tables\Filters\Filter::make('blocked_only')
                    ->label('IPs bloquées uniquement')
                    ->query(fn (Builder $q) => $q->where('ip_blocked', true)),

                Tables\Filters\Filter::make('today')
                    ->label("Aujourd'hui")
                    ->query(fn (Builder $q) => $q->whereDate('created_at', today())),

                Tables\Filters\Filter::make('last_hour')
                    ->label('Dernière heure')
                    ->query(fn (Builder $q) => $q->where('created_at', '>=', now()->subHour())),
            ])
            ->actions([
                Tables\Actions\Action::make('view_details')
                    ->label('Détails')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading('Détails de l\'événement')
                    ->infolist(
                        fn (Infolist $infolist) => $infolist
                        ->schema([
                            Infolists\Components\TextEntry::make('type')
                                ->label('Type'),
                            Infolists\Components\TextEntry::make('severity')
                                ->label('Sévérité'),
                            Infolists\Components\TextEntry::make('ip')
                                ->label('Adresse IP'),
                            Infolists\Components\TextEntry::make('country')
                                ->label('Pays'),
                            Infolists\Components\TextEntry::make('city')
                                ->label('Ville'),
                            Infolists\Components\TextEntry::make('email')
                                ->label('Email'),
                            Infolists\Components\TextEntry::make('method')
                                ->label('Méthode HTTP'),
                            Infolists\Components\TextEntry::make('url')
                                ->label('URL'),
                            Infolists\Components\TextEntry::make('referer')
                                ->label('Referer'),
                            Infolists\Components\TextEntry::make('user_agent')
                                ->label('User Agent'),
                            Infolists\Components\TextEntry::make('metadata')
                                ->label('Métadonnées')
                                ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '—'),
                            Infolists\Components\TextEntry::make('created_at')
                                ->label('Date')
                                ->dateTime('d/m/Y H:i:s'),
                        ])
                    )
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fermer'),

                Tables\Actions\Action::make('block_ip')
                    ->label('Bloquer IP')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription(fn (SecurityEvent $r) => "Bloquer l'IP {$r->ip} sur tout le site ?")
                    ->visible(fn (SecurityEvent $r) => $r->ip !== null && ! $r->ip_blocked)
                    ->action(function (SecurityEvent $record) {
                        if ($record->ip === null) {
                            return;
                        }
                        BlockedIp::firstOrCreate(
                            ['ip' => $record->ip],
                            ['reason' => "Sécurité ({$record->type})", 'blocked_by' => auth()->id()]
                        );
                        SecurityEvent::where('ip', $record->ip)->update(['ip_blocked' => true]);
                        Cache::forget("blocked_ip:{$record->ip}");
                        Notification::make()->title("IP {$record->ip} bloquée")->success()->send();
                    }),

                Tables\Actions\Action::make('unblock_ip')
                    ->label('Débloquer')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (SecurityEvent $r) => $r->ip_blocked)
                    ->action(function (SecurityEvent $record) {
                        if ($record->ip === null) {
                            return;
                        }
                        BlockedIp::where('ip', $record->ip)->delete();
                        SecurityEvent::where('ip', $record->ip)->update(['ip_blocked' => false]);
                        Cache::forget("blocked_ip:{$record->ip}");
                        Notification::make()->title("IP {$record->ip} débloquée")->success()->send();
                    }),

                Tables\Actions\DeleteAction::make()->label('Supprimer'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_block')
                        ->label('Bloquer IPs sélectionnées')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $ips = $records->pluck('ip')->filter()->unique()->values();
                            foreach ($ips as $ip) {
                                BlockedIp::firstOrCreate(
                                    ['ip' => $ip],
                                    ['reason' => 'Blocage en masse', 'blocked_by' => auth()->id()]
                                );
                                Cache::forget("blocked_ip:{$ip}");
                            }
                            SecurityEvent::whereIn('ip', $ips->all())->update(['ip_blocked' => true]);
                            Notification::make()->title("{$ips->count()} IP(s) bloquée(s)")->success()->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make()->label('Supprimer'),
                ]),
            ])
            ->poll('30s');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([]);
    }

    public static function getRelationManagers(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSecurityEvents::route('/'),
        ];
    }
}
