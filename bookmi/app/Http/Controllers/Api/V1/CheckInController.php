<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TrackingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CheckInRequest;
use App\Models\BookingRequest;
use App\Models\TalentProfile;
use App\Services\TrackingService;
use Illuminate\Http\JsonResponse;

class CheckInController extends Controller
{
    public function __construct(
        private readonly TrackingService $trackingService,
    ) {
    }

    /**
     * POST /booking_requests/{booking}/checkin
     *
     * Records the talent's physical arrival at the event venue.
     * Coordinates are mandatory. Advances tracking status to 'arrived'.
     */
    public function store(CheckInRequest $request, BookingRequest $booking): JsonResponse
    {
        $user = $request->user();

        $isTalent = TalentProfile::where('id', $booking->talent_profile_id)
            ->where('user_id', $user->id)
            ->exists();

        if (! $isTalent) {
            return response()->json([
                'error' => [
                    'code'    => 'FORBIDDEN',
                    'message' => 'Only the talent can check in for a booking.',
                ],
            ], 403);
        }

        $event = $this->trackingService->sendUpdate(
            booking: $booking,
            talent: $user,
            status: TrackingStatus::Arrived,
            latitude: (float) $request->validated('latitude'),
            longitude: (float) $request->validated('longitude'),
        );

        return response()->json([
            'data' => [
                'id'                 => $event->id,
                'booking_request_id' => $event->booking_request_id,
                'status'             => $event->status->value,
                'status_label'       => $event->status->label(),
                'latitude'           => $event->latitude,
                'longitude'          => $event->longitude,
                'occurred_at'        => $event->occurred_at?->toISOString(),
            ],
            'message' => 'Check-in enregistré avec succès.',
        ], 201);
    }
}
