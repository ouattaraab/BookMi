<?php

namespace App\Http\Resources;

use App\Models\CalendarSlot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CalendarSlot */
class CalendarSlotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'talent_profile_id' => $this->talent_profile_id,
            'date'              => $this->date->toDateString(),
            'status'            => $this->status->value,
        ];
    }
}
