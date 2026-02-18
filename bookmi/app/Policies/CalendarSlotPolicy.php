<?php

namespace App\Policies;

use App\Models\CalendarSlot;
use App\Models\User;

class CalendarSlotPolicy
{
    public function modify(User $user, CalendarSlot $slot): bool
    {
        // Use a targeted query to avoid loading the full TalentProfile model
        return \App\Models\TalentProfile::where('id', $slot->talent_profile_id)
            ->where('user_id', $user->id)
            ->exists();
    }

    public function update(User $user, CalendarSlot $slot): bool
    {
        return $this->modify($user, $slot);
    }

    public function delete(User $user, CalendarSlot $slot): bool
    {
        return $this->modify($user, $slot);
    }
}
