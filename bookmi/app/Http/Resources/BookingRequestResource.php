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
                'name' => trim($this->client->first_name . ' ' . $this->client->last_name),
            ],
            'talent_profile'  => [
                'id'         => $this->talentProfile->id,
                'stage_name' => $this->talentProfile->stage_name,
                'slug'       => $this->talentProfile->slug,
            ],
            'created_at'      => $this->created_at?->toIso8601String(),
            'service_package' => [
                'id'               => $this->servicePackage->id,
                'name'             => $this->servicePackage->name,
                'type'             => $this->servicePackage->type->value,
                'description'      => $this->servicePackage->description,
                'inclusions'       => $this->servicePackage->inclusions,
                'duration_minutes' => $this->servicePackage->duration_minutes,
            ],
            'event_date'      => $this->event_date->toDateString(),
            'event_location'  => $this->event_location,
            'message'         => $this->message,
            'is_express'                  => $this->is_express,
            'reject_reason'               => $this->reject_reason,
            'refund_amount'               => $this->refund_amount,
            'cancellation_policy_applied' => $this->cancellation_policy_applied,
            'contract_available'          => $this->contract_path !== null,
            'devis'           => [
                'cachet_amount'     => $this->cachet_amount,
                'commission_amount' => $this->commission_amount,
                'total_amount'      => $this->total_amount,
                'message'           => sprintf(
                    'Cachet artiste intact — BookMi ajoute %d%% de frais de service',
                    (int) config('bookmi.commission_rate', 15),
                ),
            ],
        ];
    }
}
