<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Enums\EscrowStatus;
use App\Enums\TrackingStatus;
use App\Http\Controllers\Controller;
use App\Jobs\SendPushNotification;
use App\Models\BookingRequest;
use App\Models\EscrowHold;
use App\Models\TrackingEvent;
use App\Services\EscrowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfirmArrivalController extends Controller
{
    public function __construct(
        private readonly EscrowService $escrowService,
    ) {
    }

    /**
     * POST /api/v1/booking_requests/{booking}/confirm-arrival
     *
     * Client confirms the talent has arrived on site.
     * This triggers escrow release if the booking is still in Paid status.
     */
    public function store(Request $request, BookingRequest $booking): JsonResponse
    {
        $user = $request->user();

        // Guard: only the client can confirm arrival
        if ($booking->client_id !== $user->id) {
            return response()->json([
                'error' => [
                    'code'    => 'FORBIDDEN',
                    'message' => 'Seul le client peut confirmer la présence du talent.',
                ],
            ], 403);
        }

        // Guard: booking must be paid or confirmed
        if (! in_array($booking->status, [BookingStatus::Paid, BookingStatus::Confirmed], strict: true)) {
            return response()->json([
                'error' => [
                    'code'    => 'INVALID_STATUS',
                    'message' => 'La réservation doit être payée ou confirmée pour valider la présence.',
                ],
            ], 422);
        }

        // Guard: talent must have signalled arrival
        $hasArrived = TrackingEvent::where('booking_request_id', $booking->id)
            ->where('status', TrackingStatus::Arrived->value)
            ->exists();

        if (! $hasArrived) {
            return response()->json([
                'error' => [
                    'code'    => 'TALENT_NOT_ARRIVED',
                    'message' => "Le talent n'a pas encore signalé son arrivée.",
                ],
            ], 422);
        }

        // Idempotency: already confirmed
        if ($booking->client_confirmed_arrival_at !== null) {
            return response()->json([
                'error' => [
                    'code'    => 'ALREADY_CONFIRMED',
                    'message' => 'La présence a déjà été confirmée.',
                ],
            ], 409);
        }

        // Record confirmation timestamp
        $booking->update(['client_confirmed_arrival_at' => now()]);

        // Release escrow if still held (booking = Paid)
        $escrowReleased = false;
        if ($booking->status === BookingStatus::Paid) {
            $hold = EscrowHold::where('booking_request_id', $booking->id)
                ->where('status', EscrowStatus::Held->value)
                ->first();

            if ($hold) {
                $this->escrowService->releaseEscrow($hold);
                $escrowReleased = true;
            }
        }

        // Refresh booking to get updated status after escrow release
        $booking->refresh();

        // Push notification to talent
        $this->notifyTalent($booking, $escrowReleased);

        return response()->json([
            'data' => [
                'client_confirmed_arrival_at' => $booking->client_confirmed_arrival_at?->toISOString(),
                'booking_status'              => $booking->status->value,
            ],
        ]);
    }

    private function notifyTalent(BookingRequest $booking, bool $escrowReleased): void
    {
        $talentProfile = $booking->talentProfile()->with('user')->first();
        if (! $talentProfile?->user) {
            return;
        }

        $body = $escrowReleased
            ? 'Le client a confirmé votre présence sur place. Vos fonds ont été libérés ! 💰'
            : 'Le client a confirmé votre présence sur place. Vos fonds ont été sécurisés.';

        SendPushNotification::dispatch(
            $talentProfile->user->id,
            'Présence confirmée ✅',
            $body,
            [
                'type'       => 'booking_updates',
                'booking_id' => (string) $booking->id,
            ],
        );
    }
}
