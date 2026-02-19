<?php

namespace App\Filament\Pages;

use App\Models\BookingRequest;
use App\Models\TalentProfile;
use App\Models\User;
use Filament\Pages\Page;

class KpisPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'KPIs Plateforme';
    protected static ?string $title = 'KPIs Plateforme';
    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.kpis-page';

    public array $kpis = [];
    public array $monthlyRevenue = [];

    public function mount(): void
    {
        $this->kpis = $this->computeKpis();
        $this->monthlyRevenue = $this->computeMonthlyRevenue();
    }

    private function computeKpis(): array
    {
        $totalUsers = User::where('is_admin', false)->count();
        $totalTalents = TalentProfile::count();
        $totalClients = User::where('is_admin', false)
            ->doesntHave('talentProfile')
            ->count();

        $completedBookings = BookingRequest::where('status', 'completed')->count();
        $totalBookings = BookingRequest::count();
        $conversionRate = $totalBookings > 0
            ? round($completedBookings / $totalBookings * 100, 1)
            : 0;

        $totalRevenue = BookingRequest::where('status', 'completed')->sum('total_amount');
        $avgBookingValue = $completedBookings > 0
            ? round($totalRevenue / $completedBookings)
            : 0;

        return [
            'total_users'       => $totalUsers,
            'total_talents'     => $totalTalents,
            'total_clients'     => $totalClients,
            'total_bookings'    => $totalBookings,
            'completed_bookings' => $completedBookings,
            'conversion_rate'   => $conversionRate,
            'total_revenue'     => $totalRevenue,
            'avg_booking_value' => $avgBookingValue,
        ];
    }

    private function computeMonthlyRevenue(): array
    {
        $months = collect();

        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $revenue = BookingRequest::where('status', 'completed')
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->sum('total_amount');

            $months->push([
                'label'   => $month->format('M Y'),
                'revenue' => $revenue,
            ]);
        }

        return $months->all();
    }
}
