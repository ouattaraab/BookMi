<?php

namespace App\Http\Resources;

use App\Models\BookingRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BookingRequest */
class BookingRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'status'          => $this->status->value,
            'client'          => [
                'id'   => $this->client->id,
                'name' => $this->client->name,
            ],
            'talent_profile'  => [
                'id'         => $this->talentProfile->id,
                'stage_name' => $this->talentProfile->stage_name,
            ],
            'service_package' => [
                'id'   => $this->servicePackage->id,
                'name' => $this->servicePackage->name,
                'type' => $this->servicePackage->type->value,
            ],
            'event_date'      => $this->event_date->toDateString(),
            'event_location'  => $this->event_location,
            'message'         => $this->message,
            'reject_reason'   => $this->reject_reason,
            'devis'           => [
                'cachet_amount'     => $this->cachet_amount,
                'commission_amount' => $this->commission_amount,
                'total_amount'      => $this->total_amount,
            ],
        ];
    }
}
