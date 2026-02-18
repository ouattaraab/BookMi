<?php

namespace App\Services;

use App\Enums\CalendarSlotStatus;
use App\Exceptions\CalendarException;
use App\Models\CalendarSlot;
use App\Models\TalentProfile;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        // 2. Load confirmed booking dates (anticipate booking_requests table from story 3.2)
        //    If the table doesn't exist yet we skip gracefully.
        $confirmedDates = collect();
        if ($this->bookingTableExists()) {
            $confirmedDates = $this->getConfirmedBookingDates($talent->id, $start, $end);
        }

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
     * Check whether a specific date is available for booking.
     *
     * A date is unavailable when:
     *  - There is a calendar slot with status `blocked` or `rest`
     *  - There is a confirmed booking_request on that date
     */
    public function isDateAvailable(TalentProfile $talent, string $date): bool
    {
        $hasBlockingSlot = CalendarSlot::where('talent_profile_id', $talent->id)
            ->where('date', $date)
            ->whereIn('status', [CalendarSlotStatus::Blocked->value, CalendarSlotStatus::Rest->value])
            ->exists();

        if ($hasBlockingSlot) {
            return false;
        }

        if ($this->bookingTableExists()) {
            $hasConfirmedBooking = DB::table('booking_requests')
                ->where('talent_profile_id', $talent->id)
                ->where('event_date', $date)
                ->where('status', 'confirmed')
                ->exists();

            if ($hasConfirmedBooking) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check whether the booking_requests table exists (it is created in story 3.2).
     */
    private function bookingTableExists(): bool
    {
        return Schema::hasTable('booking_requests');
    }

    /**
     * @return Collection<int, string>
     */
    private function getConfirmedBookingDates(int $talentProfileId, Carbon $start, Carbon $end): Collection
    {
        return DB::table('booking_requests')
            ->where('talent_profile_id', $talentProfileId)
            ->where('status', 'confirmed')
            ->whereBetween('event_date', [$start->toDateString(), $end->toDateString()])
            ->pluck('event_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString());
    }
}
