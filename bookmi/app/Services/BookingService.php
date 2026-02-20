<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\CalendarSlotStatus;
use App\Events\BookingAccepted;
use App\Events\BookingCancelled;
use App\Events\BookingCreated;
use App\Exceptions\BookingException;
use App\Jobs\GenerateContractPdf;
use App\Models\BookingRequest;
use App\Models\CalendarSlot;
use App\Models\ServicePackage;
use App\Models\TalentProfile;
use App\Models\User;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function __construct(
        private readonly CalendarService $calendarService,
    ) {
    }

    /**
     * Create a new booking request from a client.
     *
     * @param array{talent_profile_id: int, service_package_id: int, event_date: string, event_location: string, message?: string|null, is_express?: bool} $data
     */
    public function createBookingRequest(User $client, array $data): BookingRequest
    {
        $isExpress = (bool) ($data['is_express'] ?? false);

        $talentProfile = TalentProfile::find($data['talent_profile_id']);
        if (! $talentProfile) {
            throw BookingException::talentNotFound();
        }

        if ($isExpress && ! $talentProfile->enable_express_booking) {
            throw BookingException::expressBookingNotAvailable();
        }

        $package = ServicePackage::find($data['service_package_id']);
        if (! $package || $package->talent_profile_id !== $talentProfile->id) {
            throw BookingException::packageNotBelongToTalent();
        }

        if (! $this->calendarService->isDateAvailable($talentProfile, $data['event_date'])) {
            throw BookingException::dateUnavailable();
        }

        $cachetAmount     = $package->cachet_amount;
        $commissionRate   = (int) config('bookmi.commission_rate', 15);
        $commissionAmount = (int) round(($cachetAmount * $commissionRate) / 100);
        $totalAmount      = $cachetAmount + $commissionAmount;

        $booking = BookingRequest::create([
            'client_id'          => $client->id,
            'talent_profile_id'  => $talentProfile->id,
            'service_package_id' => $package->id,
            'event_date'         => $data['event_date'],
            'event_location'     => $data['event_location'],
            'message'            => $data['message'] ?? null,
            'is_express'         => $isExpress,
            'status'             => BookingStatus::Pending,
            'cachet_amount'      => $cachetAmount,
            'commission_amount'  => $commissionAmount,
            'total_amount'       => $totalAmount,
        ]);

        BookingCreated::dispatch($booking);

        if ($isExpress) {
            $booking = $this->acceptBooking($booking);
        }

        return $booking;
    }

    /**
     * Return paginated bookings for a user (client or talent side).
     *
     * - If the user has a talent profile: returns bookings received for that profile.
     * - Otherwise: returns bookings sent by the user as a client.
     *
     * @param array{status?: string|null} $filters
     */
    public function getBookingsForUser(User $user, array $filters = []): CursorPaginator
    {
        $query = BookingRequest::with([
            'client:id,first_name,last_name',
            'talentProfile:id,stage_name,slug',
            'servicePackage:id,name,type,description,inclusions,duration_minutes',
        ]);

        $talentProfile = TalentProfile::where('user_id', $user->id)->first();

        if ($talentProfile) {
            $query->where('talent_profile_id', $talentProfile->id);
        } else {
            $query->where('client_id', $user->id);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest('id')->cursorPaginate(20);
    }

    /**
     * Accept a pending booking request (talent action).
     *
     * Transitions: pending → accepted
     * Side-effects: blocks the calendar slot, dispatches BookingAccepted.
     */
    public function acceptBooking(BookingRequest $booking): BookingRequest
    {
        if (! $booking->status->canTransitionTo(BookingStatus::Accepted)) {
            throw BookingException::invalidStatusTransition();
        }

        DB::transaction(function () use ($booking) {
            $booking->update(['status' => BookingStatus::Accepted]);

            CalendarSlot::updateOrCreate(
                [
                    'talent_profile_id' => $booking->talent_profile_id,
                    'date'              => $booking->event_date->toDateString(),
                ],
                ['status' => CalendarSlotStatus::Blocked],
            );
        });

        BookingAccepted::dispatch($booking);
        GenerateContractPdf::dispatch($booking)->onQueue('media');

        return $booking;
    }

    /**
     * Cancel a booking (client action) with graduated refund policy.
     *
     * Policy (from config):
     *   >= J-14 days → full refund (100%)
     *   >= J-7 days  → partial refund (50%)
     *   >= J-2 days  → mediation required (throws)
     *   <  J-2 days  → not allowed (throws)
     */
    public function cancelBooking(BookingRequest $booking): BookingRequest
    {
        if (! $booking->status->canTransitionTo(BookingStatus::Cancelled)) {
            throw BookingException::invalidStatusTransition();
        }

        $cancellation   = config('bookmi.cancellation');
        $fullRefundDays = (int) $cancellation['full_refund_days'];
        $partialDays    = (int) $cancellation['partial_refund_days'];
        $mediationDays  = (int) $cancellation['mediation_only_days'];
        $partialRate    = (int) $cancellation['partial_refund_rate'];

        $daysUntilEvent = now()->startOfDay()->diffInDays(
            $booking->event_date->startOfDay(),
            absolute: false,
        );

        if ($daysUntilEvent < $mediationDays) {
            throw BookingException::cancellationNotAllowed();
        }

        if ($daysUntilEvent < $partialDays) {
            throw BookingException::cancellationRequiresMediation();
        }

        if ($daysUntilEvent >= $fullRefundDays) {
            $refundAmount = $booking->total_amount;
            $policy       = 'full_refund';
        } else {
            $refundAmount = (int) round($booking->total_amount * $partialRate / 100);
            $policy       = 'partial_refund';
        }

        $booking->update([
            'status'                      => BookingStatus::Cancelled,
            'refund_amount'               => $refundAmount,
            'cancellation_policy_applied' => $policy,
        ]);

        BookingCancelled::dispatch($booking);

        return $booking;
    }

    /**
     * Reject a pending booking request (talent action).
     *
     * Transitions: pending → cancelled
     * Side-effects: stores optional reject_reason, dispatches BookingCancelled.
     *
     * Only pending bookings can be rejected via this endpoint (AC3).
     * Even though the state machine allows accepted→cancelled, that is a different
     * cancellation flow (not the reject-request action).
     */
    public function rejectBooking(BookingRequest $booking, ?string $reason = null): BookingRequest
    {
        if ($booking->status !== BookingStatus::Pending) {
            throw BookingException::invalidStatusTransition();
        }

        $booking->update([
            'status'        => BookingStatus::Cancelled,
            'reject_reason' => $reason,
        ]);

        BookingCancelled::dispatch($booking);

        return $booking;
    }
}
