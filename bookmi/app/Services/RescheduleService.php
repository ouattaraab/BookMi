<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\CalendarSlotStatus;
use App\Enums\RescheduleStatus;
use App\Exceptions\BookingException;
use App\Models\BookingRequest;
use App\Models\CalendarSlot;
use App\Models\RescheduleRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RescheduleService
{
    /** Statuses that allow a reschedule request. */
    private const RESCHEDULABLE_STATUSES = [
        BookingStatus::Accepted,
        BookingStatus::Paid,
        BookingStatus::Confirmed,
    ];

    /**
     * Create a reschedule request for a booking.
     *
     * @param array{proposed_date: string, message?: string|null} $data
     */
    public function createReschedule(BookingRequest $booking, User $requester, array $data): RescheduleRequest
    {
        if (! in_array($booking->status, self::RESCHEDULABLE_STATUSES, strict: true)) {
            throw BookingException::invalidStatusTransition();
        }

        if ($booking->event_date->toDateString() === $data['proposed_date']) {
            throw BookingException::rescheduleSameDate();
        }

        if ($booking->hasPendingReschedule()) {
            throw BookingException::rescheduleAlreadyPending();
        }

        return RescheduleRequest::create([
            'booking_request_id' => $booking->id,
            'requested_by_id'    => $requester->id,
            'proposed_date'      => $data['proposed_date'],
            'message'            => $data['message'] ?? null,
            'status'             => RescheduleStatus::Pending,
        ]);
    }

    /**
     * Accept a pending reschedule request (counterparty action).
     * Updates the booking's event_date and calendar slots.
     */
    public function acceptReschedule(RescheduleRequest $reschedule): RescheduleRequest
    {
        if ($reschedule->status !== RescheduleStatus::Pending) {
            throw BookingException::rescheduleNotPending();
        }

        $booking = $reschedule->booking;

        DB::transaction(function () use ($reschedule, $booking) {
            // Free the old slot
            CalendarSlot::where('talent_profile_id', $booking->talent_profile_id)
                ->where('date', $booking->event_date->toDateString())
                ->where('status', CalendarSlotStatus::Blocked)
                ->update(['status' => CalendarSlotStatus::Available]);

            // Block the new slot
            CalendarSlot::updateOrCreate(
                [
                    'talent_profile_id' => $booking->talent_profile_id,
                    'date'              => $reschedule->proposed_date->toDateString(),
                ],
                ['status' => CalendarSlotStatus::Blocked],
            );

            // Update booking date
            $booking->update(['event_date' => $reschedule->proposed_date->toDateString()]);

            // Mark reschedule as accepted
            $reschedule->update([
                'status'       => RescheduleStatus::Accepted,
                'responded_at' => now(),
            ]);
        });

        return $reschedule;
    }

    /**
     * Reject a pending reschedule request (counterparty action).
     */
    public function rejectReschedule(RescheduleRequest $reschedule): RescheduleRequest
    {
        if ($reschedule->status !== RescheduleStatus::Pending) {
            throw BookingException::rescheduleNotPending();
        }

        $reschedule->update([
            'status'       => RescheduleStatus::Rejected,
            'responded_at' => now(),
        ]);

        return $reschedule;
    }
}
