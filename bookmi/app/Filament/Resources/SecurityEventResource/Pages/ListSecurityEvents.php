<?php

namespace App\Filament\Resources\SecurityEventResource\Pages;

use App\Filament\Resources\SecurityEventResource;
use App\Models\BlockedIp;
use App\Models\SecurityEvent;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListSecurityEvents extends ListRecords
{
    protected static string $resource = SecurityEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('clear_old')
                ->label('Purger > 30 jours')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('Supprimer tous les événements de sécurité vieux de plus de 30 jours ?')
                ->action(fn () => SecurityEvent::where('created_at', '<', now()->subDays(30))->delete()),
        ];
    }

    public function getTabs(): array
    {
        $total   = SecurityEvent::count();
        $today   = SecurityEvent::whereDate('created_at', today())->count();
        $high    = SecurityEvent::whereIn('severity', ['high', 'critical'])->whereDate('created_at', today())->count();
        $blocked = BlockedIp::count();

        return [
            'all' => Tab::make('Tous')
                ->badge($total),

            'today' => Tab::make("Aujourd'hui")
                ->badge($today)
                ->badgeColor($today > 0 ? 'warning' : 'success')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereDate('created_at', today())),

            'high' => Tab::make('Élevé / Critique')
                ->badge($high)
                ->badgeColor($high > 0 ? 'danger' : 'success')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereIn('severity', ['high', 'critical'])),

            'login' => Tab::make('Logins')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereIn('type', ['login_failed', 'login_locked'])),

            'honeypot' => Tab::make('Honeypot')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('type', 'honeypot_hit')),

            'blocked' => Tab::make('IPs bloquées')
                ->badge($blocked)
                ->badgeColor($blocked > 0 ? 'danger' : 'success')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('ip_blocked', true)),
        ];
    }
}
