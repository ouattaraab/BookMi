<?php

namespace App\Filament\Pages;

use App\Models\BookingRequest;
use App\Models\Review;
use App\Models\TalentProfile;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class QualityDashboardPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = 'Qualité';

    protected static ?string $title = 'Dashboard Qualité';

    protected static ?string $navigationGroup = 'Analyse';

    protected static ?int $navigationSort = 12;

    protected static string $view = 'filament.pages.quality-dashboard-page';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return ($user?->is_admin ?? false) || ($user?->hasAnyRole(['admin_ceo', 'admin_moderateur']) ?? false);
    }

    public array $globalMetrics   = [];
    public array $categoryRatings = [];
    public array $topTalents      = [];
    public array $alertTalents    = [];
    public array $disputeAlerts   = [];
    public array $monthlyDisputes = [];

    public function mount(): void
    {
        $this->loadData();
    }

    public function refresh(): void
    {
        $this->loadData();
    }

    private function loadData(): void
    {
        $this->globalMetrics   = $this->computeGlobalMetrics();
        $this->categoryRatings = $this->computeCategoryRatings();
        $this->topTalents      = $this->computeTopTalents();
        $this->alertTalents    = $this->computeAlertTalents();
        $this->disputeAlerts   = $this->computeDisputeAlerts();
        $this->monthlyDisputes = $this->computeMonthlyDisputes();
    }

    /** @return array<string, mixed> */
    private function computeGlobalMetrics(): array
    {
        $totalCompleted = BookingRequest::where('status', 'completed')->count();
        $totalDisputed  = BookingRequest::where('status', 'disputed')->count();
        $totalBookings  = BookingRequest::whereIn('status', ['completed', 'disputed'])->count();
        $disputeRate    = $totalBookings > 0 ? round($totalDisputed / $totalBookings * 100, 1) : 0;

        $avgRating = Review::avg('overall_rating') ?? 0;

        $avgResponse = BookingRequest::join(
            'booking_status_logs',
            'booking_requests.id',
            '=',
            'booking_status_logs.booking_request_id'
        )
            ->where('booking_status_logs.to_status', 'accepted')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, booking_requests.created_at, booking_status_logs.created_at)) as avg_hours'))
            ->value('avg_hours');

        return [
            'avg_rating'      => round((float) $avgRating, 2),
            'dispute_rate'    => $disputeRate,
            'total_reviews'   => Review::count(),
            'avg_response_h'  => $avgResponse ? round((float) $avgResponse, 1) : null,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function computeCategoryRatings(): array
    {
        return TalentProfile::join('categories', 'talent_profiles.category_id', '=', 'categories.id')
            ->join('booking_requests', 'booking_requests.talent_profile_id', '=', 'talent_profiles.id')
            ->join('reviews', 'reviews.booking_request_id', '=', 'booking_requests.id')
            ->select(
                'categories.name as category',
                DB::raw('ROUND(AVG(reviews.overall_rating), 2) as avg_rating'),
                DB::raw('COUNT(reviews.id) as review_count')
            )
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('avg_rating')
            ->get()
            ->toArray();
    }

    /** @return array<int, array<string, mixed>> */
    private function computeTopTalents(): array
    {
        return TalentProfile::with('user:id,first_name,last_name')
            ->select('id', 'user_id', 'stage_name', 'average_rating', 'total_bookings', 'city')
            ->where('total_bookings', '>=', 3)
            ->orderByDesc('average_rating')
            ->limit(10)
            ->get()
            ->map(fn ($t) => [
                'stage_name'    => $t->stage_name,
                'name'          => $t->user?->first_name . ' ' . $t->user?->last_name,
                'avg_rating'    => $t->average_rating,
                'total_bookings' => $t->total_bookings,
                'city'          => $t->city,
            ])
            ->toArray();
    }

    /** @return array<int, array<string, mixed>> */
    private function computeAlertTalents(): array
    {
        return TalentProfile::with('user:id,first_name,last_name,email')
            ->select('id', 'user_id', 'stage_name', 'average_rating', 'total_bookings', 'city')
            ->where('total_bookings', '>=', 3)
            ->where('average_rating', '<', 3.5)
            ->orderBy('average_rating')
            ->limit(20)
            ->get()
            ->map(fn ($t) => [
                'stage_name'    => $t->stage_name,
                'email'         => $t->user?->email,
                'avg_rating'    => $t->average_rating,
                'total_bookings' => $t->total_bookings,
                'city'          => $t->city,
            ])
            ->toArray();
    }

    /** @return array<int, array<string, mixed>> */
    private function computeDisputeAlerts(): array
    {
        return TalentProfile::with('user:id,first_name,last_name,email')
            ->withCount(['bookingRequests as disputes_last_30d' => function ($q): void {
                $q->where('status', 'disputed')->where('updated_at', '>=', now()->subDays(30));
            }])
            ->having('disputes_last_30d', '>=', 2)
            ->select('id', 'user_id', 'stage_name', 'city')
            ->orderByDesc('disputes_last_30d')
            ->limit(20)
            ->get()
            ->map(fn ($t) => [
                'stage_name'       => $t->stage_name,
                'email'            => $t->user?->email,
                'disputes_last_30d' => $t->disputes_last_30d,
                'city'             => $t->city,
            ])
            ->toArray();
    }

    /** @return array<int, array<string, mixed>> */
    private function computeMonthlyDisputes(): array
    {
        return BookingRequest::select(
            DB::raw("DATE_FORMAT(updated_at, '%Y-%m') as month"),
            DB::raw('COUNT(*) as count')
        )
            ->where('status', 'disputed')
            ->where('updated_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();
    }
}
