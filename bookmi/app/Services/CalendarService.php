<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\CalendarSlotStatus;
use App\Exceptions\CalendarException;
use App\Models\CalendarSlot;
use App\Models\TalentProfile;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CalendarService
{
    /**
     * Create a new calendar slot for a talent.
     *
     * @param  array{date: string, status: string}  $data
     */
    public function createSlot(TalentProfile $talent, array $data): CalendarSlot
    {
        try {
            $slot = CalendarSlot::create([
                'talent_profile_id' => $talent->id,
                'date'              => $data['date'],
                'status'            => $data['status'],
            ]);
        } catch (UniqueConstraintViolationException) {
            throw CalendarException::slotConflict();
        }

        return $slot;
    }

    /**
     * Update a calendar slot status.
     *
     * @param  array{status: string}  $data
     */
    public function updateSlot(CalendarSlot $slot, array $data): CalendarSlot
    {
        $slot->update(['status' => $data['status']]);

        return $slot;
    }

    /**
     * Delete a calendar slot.
     */
    public function deleteSlot(CalendarSlot $slot): void
    {
        $slot->delete();
    }

    /**
     * Get all slots for a talent in a given month, merged with confirmed bookings.
     *
     * Month format: Y-m (e.g. 2026-03)
     *
     * @return Collection<int, array{date: string, status: string, slot_id: int|null}>
     */
    public function getMonthCalendar(TalentProfile $talent, string $month): Collection
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            throw CalendarException::invalidMonth();
        }

        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        // 1. Load explicit calendar slots for the month
        $slots = CalendarSlot::where('talent_profile_id', $talent->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn (CalendarSlot $s) => $s->date->toDateString());

        // 2. Load confirmed booking dates and merge virtual 'confirmed' status
        $confirmedDates = $this->getConfirmedBookingDates($talent->id, $start, $end);

        // 3. Merge: confirmed bookings override manual slots
        $calendar = $slots->map(fn (CalendarSlot $slot) => [
            'date'    => $slot->date->toDateString(),
            'status'  => $confirmedDates->contains($slot->date->toDateString())
                            ? CalendarSlotStatus::Confirmed->value
                            : $slot->status->value,
            'slot_id' => $slot->id,
        ]);

        // 4. Add confirmed dates that don't have an explicit slot
        foreach ($confirmedDates as $date) {
            if (! $calendar->has($date)) {
                $calendar->put($date, [
                    'date'    => $date,
                    'status'  => CalendarSlotStatus::Confirmed->value,
                    'slot_id' => null,
                ]);
            }
        }

        return $calendar->values()->sortBy('date')->values();
    }

    /**
     * Check whether a specific date (and optional start time) is available for booking.
     *
     * A date/time is unavailable when:
     *  - There is a calendar slot with status `blocked` or `rest`
     *  - (no start_time) There is a confirmed booking_request on that date
     *  - (with start_time) There is an accepted/paid/confirmed booking within ±1h of the requested time
     */
    public function isDateAvailable(TalentProfile $talent, string $date, ?string $startTime = null): bool
    {
        // 1. Day-level blockers always apply regardless of time
        $hasBlockingSlot = CalendarSlot::where('talent_profile_id', $talent->id)
            ->where('date', $date)
            ->whereIn('status', [CalendarSlotStatus::Blocked->value, CalendarSlotStatus::Rest->value])
            ->exists();

        if ($hasBlockingSlot) {
            return false;
        }

        // 2a. No start_time: fall back to day-level confirmed-booking check
        if ($startTime === null) {
            $hasConfirmedBooking = DB::table('booking_requests')
                ->where('talent_profile_id', $talent->id)
                ->where('event_date', $date)
                ->where('status', BookingStatus::Confirmed->value)
                ->exists();

            return ! $hasConfirmedBooking;
        }

        // 2b. start_time provided: check ±1h buffer against active bookings with a known time
        $existingTimes = DB::table('booking_requests')
            ->where('talent_profile_id', $talent->id)
            ->where('event_date', $date)
            ->whereIn('status', [
                BookingStatus::Accepted->value,
                BookingStatus::Paid->value,
                BookingStatus::Confirmed->value,
            ])
            ->whereNotNull('start_time')
            ->pluck('start_time');

        $newTime = Carbon::createFromTimeString($startTime);

        foreach ($existingTimes as $existingTime) {
            $existing     = Carbon::createFromTimeString($existingTime);
            $diffMinutes  = abs($newTime->diffInMinutes($existing));

            if ($diffMinutes < 60) {
                return false; // Within 1-hour buffer — conflict
            }
        }

        return true;
    }

    /**
     * @return Collection<int, string>
     */
    private function getConfirmedBookingDates(int $talentProfileId, Carbon $start, Carbon $end): Collection
    {
        return DB::table('booking_requests')
            ->where('talent_profile_id', $talentProfileId)
            ->where('status', BookingStatus::Confirmed->value)
            ->whereBetween('event_date', [$start->toDateString(), $end->toDateString()])
            ->pluck('event_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString());
    }
}
