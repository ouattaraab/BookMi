<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Models\BookingRequest;
use App\Models\TalentProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends BaseController
{
    public function dashboard(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $talent = $user->talentProfile;

        if (! $talent) {
            return $this->errorResponse('TALENT_PROFILE_NOT_FOUND', 'Profil talent introuvable.', 404);
        }

        return $this->successResponse($this->buildAnalytics($talent));
    }

    private function buildAnalytics(TalentProfile $talent): array
    {
        $now = Carbon::now();
        $twelveMonthsAgo = $now->copy()->subMonths(11)->startOfMonth();

        // Bookings by status (all time)
        $bookingsByStatus = BookingRequest::where('talent_profile_id', $talent->id)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Monthly revenue (last 12 months, completed bookings) — PHP-level grouping for DB portability
        $completedBookings = BookingRequest::where('talent_profile_id', $talent->id)
            ->where('status', BookingStatus::Completed->value)
            ->where('event_date', '>=', $twelveMonthsAgo)
            ->get(['event_date', 'total_amount']);

        $monthlyRevenue = $completedBookings
            ->groupBy(fn ($b) => \Carbon\Carbon::parse($b->event_date)->format('Y-m'))
            ->map(fn ($group, $month) => [
                'month' => $month,
                'revenue_xof' => (int) $group->sum('total_amount'),
                'bookings_count' => $group->count(),
            ])
            ->sortKeys()
            ->values()
            ->toArray();

        // Rating history (last 20 reviews)
        $ratingHistory = DB::table('reviews')
            ->where('reviewee_id', $talent->user_id)
            ->where('type', 'client_to_talent')
            ->orderByDesc('created_at')
            ->limit(20)
            ->select('rating', 'created_at')
            ->get()
            ->map(fn ($row) => [
                'month' => \Carbon\Carbon::parse($row->created_at)->format('Y-m'),
                'rating' => (int) $row->rating,
            ])
            ->values()
            ->toArray();

        // Current month stats
        $currentMonthRevenue = (int) BookingRequest::where('talent_profile_id', $talent->id)
            ->where('status', BookingStatus::Completed->value)
            ->whereMonth('event_date', $now->month)
            ->whereYear('event_date', $now->year)
            ->sum('total_amount');

        $pendingBookings = BookingRequest::where('talent_profile_id', $talent->id)
            ->where('status', BookingStatus::Pending->value)
            ->count();

        return [
            'talent_profile_id' => $talent->id,
            'stage_name' => $talent->stage_name,
            'talent_level' => $talent->talent_level?->value,
            'average_rating' => (float) $talent->average_rating,
            'total_bookings' => $talent->total_bookings,
            'pending_bookings' => $pendingBookings,
            'current_month_revenue_xof' => $currentMonthRevenue,
            'bookings_by_status' => $bookingsByStatus,
            'monthly_revenue' => $monthlyRevenue,
            'rating_history' => $ratingHistory,
        ];
    }
}
