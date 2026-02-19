<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Models\BookingRequest;
use App\Models\Review;
use App\Models\TalentProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminKpiController extends BaseController
{
    /**
     * GET /api/v1/admin/kpis
     * Platform KPI dashboard (Story 8.12).
     */
    public function index(Request $request): JsonResponse
    {
        $now         = Carbon::now();
        $monthStart  = $now->copy()->startOfMonth();
        $prevStart   = $now->copy()->subMonth()->startOfMonth();
        $prevEnd     = $now->copy()->subMonth()->endOfMonth();

        // Registrations
        $totalUsers        = User::count();
        $usersThisMonth    = User::where('created_at', '>=', $monthStart)->count();
        $usersPrevMonth    = User::whereBetween('created_at', [$prevStart, $prevEnd])->count();

        // Booking conversion: requests that became Paid or beyond / total requests
        $totalRequests = BookingRequest::count();
        $paidOrBeyond  = BookingRequest::whereIn('status', [
            BookingStatus::Paid, BookingStatus::Confirmed,
            BookingStatus::Completed,
        ])->count();
        $conversionRate = $totalRequests > 0 ? round(($paidOrBeyond / $totalRequests) * 100, 2) : 0;

        // Dispute rate
        $disputeBase = BookingRequest::whereIn('status', [BookingStatus::Completed, BookingStatus::Disputed])->count();
        $disputed    = BookingRequest::where('status', BookingStatus::Disputed)->count();
        $disputeRate = $disputeBase > 0 ? round(($disputed / $disputeBase) * 100, 2) : 0;

        // Revenue
        $totalRevenue      = BookingRequest::where('status', BookingStatus::Completed)->sum('commission_amount');
        $thisMonthRevenue  = BookingRequest::where('status', BookingStatus::Completed)
            ->where('created_at', '>=', $monthStart)->sum('commission_amount');
        $prevMonthRevenue  = BookingRequest::where('status', BookingStatus::Completed)
            ->whereBetween('created_at', [$prevStart, $prevEnd])->sum('commission_amount');

        // Rating
        $avgRating = Review::avg('rating') ?? 0;

        // Monthly trend (last 12 months) using PHP-level grouping
        $monthlyRevenue = BookingRequest::where('status', BookingStatus::Completed)
            ->where('created_at', '>=', $now->copy()->subMonths(12)->startOfMonth())
            ->get(['created_at', 'commission_amount'])
            ->groupBy(fn ($b) => Carbon::parse($b->created_at)->format('Y-m'))
            ->map(fn ($group, $month) => [
                'month'          => $month,
                'revenue_xof'    => $group->sum('commission_amount'),
                'bookings_count' => $group->count(),
            ])
            ->values();

        return $this->successResponse([
            'users' => [
                'total'           => $totalUsers,
                'this_month'      => $usersThisMonth,
                'prev_month'      => $usersPrevMonth,
                'trend'           => $usersPrevMonth > 0 ? round(($usersThisMonth - $usersPrevMonth) / $usersPrevMonth * 100, 1) : null,
            ],
            'conversion_rate'  => $conversionRate,
            'dispute_rate'     => $disputeRate,
            'revenue' => [
                'total_xof'       => $totalRevenue,
                'this_month_xof'  => $thisMonthRevenue,
                'prev_month_xof'  => $prevMonthRevenue,
                'trend'           => $prevMonthRevenue > 0 ? round(($thisMonthRevenue - $prevMonthRevenue) / $prevMonthRevenue * 100, 1) : null,
            ],
            'average_rating'   => round((float) $avgRating, 2),
            'talents'          => [
                'total'    => TalentProfile::count(),
                'verified' => TalentProfile::where('is_verified', true)->count(),
            ],
            'monthly_revenue'  => $monthlyRevenue,
        ]);
    }
}
