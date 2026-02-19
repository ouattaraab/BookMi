<?php

namespace App\Filament\Widgets;

use App\Models\AdminAlert;
use App\Models\Review;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AlertsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $openAlerts = AdminAlert::where('status', 'open')->count();
        $criticalAlerts = AdminAlert::where('status', 'open')->where('severity', 'critical')->count();
        $reportedReviews = Review::where('is_reported', true)->count();

        return [
            Stat::make('Alertes ouvertes', $openAlerts)
                ->description('Comportements suspects non résolus')
                ->color($openAlerts > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-bell-alert'),

            Stat::make('Alertes critiques', $criticalAlerts)
                ->description('Nécessitent une action immédiate')
                ->color($criticalAlerts > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-triangle'),

            Stat::make('Avis signalés', $reportedReviews)
                ->description('En attente de modération')
                ->color($reportedReviews > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-flag'),
        ];
    }
}
