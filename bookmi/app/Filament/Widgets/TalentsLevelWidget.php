<?php

namespace App\Filament\Widgets;

use App\Models\TalentProfile;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TalentsLevelWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $total = TalentProfile::count();
        $eliteCount = TalentProfile::where('talent_level', 'elite')->count();
        $popularCount = TalentProfile::where('talent_level', 'popular')->count();
        $confirmedCount = TalentProfile::where('talent_level', 'confirmed')->count();
        $verifiedCount = TalentProfile::where('is_verified', true)->count();

        return [
            Stat::make('Total talents', $total)
                ->description("{$verifiedCount} vérifiés")
                ->color('primary')
                ->icon('heroicon-o-star'),

            Stat::make('Talents élite', $eliteCount)
                ->description("{$popularCount} populaires · {$confirmedCount} confirmés")
                ->color('warning')
                ->icon('heroicon-o-trophy'),

            Stat::make('Talents vérifiés', $verifiedCount)
                ->description(
                    $total > 0
                        ? round($verifiedCount / $total * 100) . '% du total'
                        : 'Aucun talent'
                )
                ->color('success')
                ->icon('heroicon-o-check-badge'),
        ];
    }
}
