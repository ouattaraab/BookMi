<?php

namespace App\Http\Resources;

use App\Models\RescheduleRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RescheduleRequest */
class RescheduleRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'booking_id'    => $this->booking_request_id,
            'requested_by'  => [
                'id'   => $this->requestedBy->id,
                'name' => $this->requestedBy->name,
            ],
            'proposed_date' => $this->proposed_date->toDateString(),
            'message'       => $this->message,
            'status'        => $this->status->value,
            'responded_at'  => $this->responded_at?->toIso8601String(),
            'created_at'    => $this->created_at->toIso8601String(),
        ];
    }
}
