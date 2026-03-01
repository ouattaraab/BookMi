<?php

namespace App\Filament\Pages;

use App\Models\AppEvent;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class UsageAnalyticsPage extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-cursor-arrow-rays';
    protected static ?string $navigationLabel = 'Statistiques d\'usage';
    protected static ?string $title           = 'Statistiques d\'usage';
    protected static ?string $navigationGroup = 'Analytique';
    protected static ?int    $navigationSort  = 20;

    protected static string $view = 'filament.pages.usage-analytics-page';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return ($user?->is_admin ?? false) || ($user?->hasAnyRole(['admin_ceo', 'admin_comptable']) ?? false);
    }

    public array $topPages   = [];
    public array $topButtons = [];
    public array $heatmap    = [];
    public array $days       = [];

    public function mount(): void
    {
        $this->days       = $this->last14Days();
        $this->topPages   = $this->queryTop('page_view', 10);
        $this->topButtons = $this->queryTop('button_tap', 10);
        $this->heatmap    = $this->queryHeatmap();
    }

    private function last14Days(): array
    {
        $days = [];
        for ($i = 13; $i >= 0; $i--) {
            $days[] = now()->subDays($i)->format('Y-m-d');
        }

        return $days;
    }

    private function queryTop(string $eventType, int $limit): array
    {
        return AppEvent::where('event_type', $eventType)
            ->where('created_at', '>=', now()->subDays(14)->startOfDay())
            ->select('event_name', DB::raw('COUNT(*) as count'))
            ->groupBy('event_name')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['name' => $row->event_name, 'count' => (int) $row->getAttribute('count')])
            ->all();
    }

    private function queryHeatmap(): array
    {
        $since = now()->subDays(13)->startOfDay();

        $rows = AppEvent::where('event_type', 'page_view')
            ->where('created_at', '>=', $since)
            ->select('event_name', DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as count'))
            ->groupBy('event_name', 'day')
            ->orderBy('event_name')
            ->orderBy('day')
            ->get();

        // Build page → [day => count] map
        $pages = [];
        foreach ($rows as $row) {
            $pages[$row->event_name][(string) $row->getAttribute('day')] = (int) $row->getAttribute('count');
        }

        // Sort pages by total descending, keep top 15
        arsort($pages);
        $pages = array_slice($pages, 0, 15, true);

        $heatmap = [];
        foreach ($pages as $page => $dayCounts) {
            $heatmap[] = [
                'page' => $page,
                'days' => $dayCounts,
            ];
        }

        return $heatmap;
    }

    public function colorClass(int $count, int $max): string
    {
        if ($count === 0 || $max === 0) {
            return 'bg-gray-100 dark:bg-gray-800 text-gray-400';
        }

        $ratio = $count / $max;

        return match (true) {
            $ratio >= 0.85 => 'bg-blue-700 text-white',
            $ratio >= 0.65 => 'bg-blue-500 text-white',
            $ratio >= 0.45 => 'bg-blue-400 text-white',
            $ratio >= 0.30 => 'bg-blue-300 text-gray-800',
            $ratio >= 0.15 => 'bg-blue-200 text-gray-800',
            default        => 'bg-blue-100 text-gray-700',
        };
    }
}
