<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Models\BookingRequest;
use Illuminate\Http\JsonResponse;

class AdminOperationsController extends BaseController
{
    /**
     * GET /api/v1/admin/operations
     * Today's services with tracking status (Story 8.8).
     */
    public function index(): JsonResponse
    {
        $today = now()->toDateString();

        $bookings = BookingRequest::whereDate('event_date', $today)
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])
            ->with([
                'client:id,first_name,last_name',
                'talentProfile:id,stage_name',
                'trackingEvents',
            ])
            ->get()
            ->map(function (BookingRequest $booking) {
                $events    = $booking->trackingEvents;
                $checkIn   = $events->firstWhere('type', 'check_in');
                $checkOut  = $events->firstWhere('type', 'check_out');

                $trackingStatus = 'upcoming';
                if ($checkOut) {
                    $trackingStatus = 'completed';
                } elseif ($checkIn) {
                    $trackingStatus = 'in_progress';
                } elseif (now()->isAfter(now()->setTimeFrom(\Carbon\Carbon::parse($booking->event_date))->addMinutes(30))) {
                    $trackingStatus = 'late';
                }

                return [
                    'id'              => $booking->id,
                    'event_date'      => $booking->event_date,
                    'event_location'  => $booking->event_location,
                    'client'          => $booking->client,
                    'talent'          => $booking->talentProfile,
                    'tracking_status' => $trackingStatus,
                    'check_in_at'     => $checkIn?->occurred_at,
                    'check_out_at'    => $checkOut?->occurred_at,
                ];
            })
            ->sortBy(fn ($b) => match ($b['tracking_status']) {
                'late'      => 0,
                'in_progress' => 1,
                'upcoming'  => 2,
                'completed' => 3,
                default     => 4,
            })
            ->values();

        return $this->successResponse($bookings);
    }
}
