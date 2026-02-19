<?php

namespace App\Filament\Widgets;

use App\Models\BookingRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BookingsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth();

        $todayCount = BookingRequest::whereDate('created_at', $today)->count();
        $monthCount = BookingRequest::where('created_at', '>=', $monthStart)->count();
        $pendingCount = BookingRequest::where('status', 'pending')->count();

        return [
            Stat::make('Réservations aujourd\'hui', $todayCount)
                ->description('Nouvelles demandes du jour')
                ->color('info')
                ->icon('heroicon-o-calendar'),

            Stat::make('Réservations ce mois', $monthCount)
                ->description('Depuis le début du mois')
                ->color('primary')
                ->icon('heroicon-o-chart-bar'),

            Stat::make('En attente de confirmation', $pendingCount)
                ->description('Demandes non traitées')
                ->color($pendingCount > 10 ? 'danger' : 'warning')
                ->icon('heroicon-o-clock'),
        ];
    }
}
