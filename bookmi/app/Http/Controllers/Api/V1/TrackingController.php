<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TrackingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateTrackingRequest;
use App\Models\BookingRequest;
use App\Models\TalentProfile;
use App\Services\TrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function __construct(
        private readonly TrackingService $trackingService,
    ) {
    }

    /**
     * POST /booking_requests/{booking}/tracking
     *
     * Only the talent who owns the booking can post tracking updates.
     */
    public function update(UpdateTrackingRequest $request, BookingRequest $booking): JsonResponse
    {
        $user = $request->user();

        // Guard: only the talent linked to this booking can send updates
        $isTalent = TalentProfile::where('id', $booking->talent_profile_id)
            ->where('user_id', $user->id)
            ->exists();

        if (! $isTalent) {
            return response()->json([
                'error' => [
                    'code'    => 'FORBIDDEN',
                    'message' => 'Seul le talent peut envoyer des mises à jour de suivi.',
                ],
            ], 403);
        }

        $status = TrackingStatus::from($request->validated('status'));

        $event = $this->trackingService->sendUpdate(
            booking: $booking,
            talent: $user,
            status: $status,
            latitude: $request->validated('latitude'),
            longitude: $request->validated('longitude'),
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
        ], 201);
    }

    /**
     * GET /booking_requests/{booking}/tracking
     *
     * Both client and talent can see the tracking history.
     */
    public function index(Request $request, BookingRequest $booking): JsonResponse
    {
        $user = $request->user();

        if (! $booking->isOwnedByUser($user)) {
            return response()->json([
                'error' => [
                    'code'    => 'FORBIDDEN',
                    'message' => 'Accès non autorisé.',
                ],
            ], 403);
        }

        $events = $booking->trackingEvents()
            ->get()
            ->map(fn ($e) => [
                'id'           => $e->id,
                'status'       => $e->status->value,
                'status_label' => $e->status->label(),
                'latitude'     => $e->latitude,
                'longitude'    => $e->longitude,
                'occurred_at'  => $e->occurred_at?->toISOString(),
            ]);

        return response()->json(['data' => $events]);
    }
}
