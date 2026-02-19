<?php

namespace App\Filament\Widgets;

use App\Models\BookingRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RevenueWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $monthStart = now()->startOfMonth();

        $totalRevenue = BookingRequest::where('status', 'completed')->sum('total_amount');
        $monthRevenue = BookingRequest::where('status', 'completed')
            ->where('created_at', '>=', $monthStart)
            ->sum('total_amount');
        $totalCommission = BookingRequest::where('status', 'completed')->sum('commission_amount');

        return [
            Stat::make('CA total plateforme', number_format($totalRevenue) . ' FCFA')
                ->description('Toutes réservations complétées')
                ->color('success')
                ->icon('heroicon-o-banknotes'),

            Stat::make('CA ce mois', number_format($monthRevenue) . ' FCFA')
                ->description('Réservations complétées ce mois')
                ->color('primary')
                ->icon('heroicon-o-arrow-trending-up'),

            Stat::make('Commissions totales', number_format($totalCommission) . ' FCFA')
                ->description('Revenus BookMi')
                ->color('warning')
                ->icon('heroicon-o-percent-badge'),
        ];
    }
}
