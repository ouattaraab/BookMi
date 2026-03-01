<?php

namespace App\Http\Resources;

use App\Enums\BookingStatus;
use App\Enums\RescheduleStatus;
use App\Enums\ReviewType;
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
            'talent_profile'  => $this->talentProfile ? [
                'id'         => $this->talentProfile->id,
                'stage_name' => $this->talentProfile->stage_name,
                'slug'       => $this->talentProfile->slug,
                'avatar_url' => $this->talentProfile->user?->avatar_url,
            ] : null,
            'created_at'      => $this->created_at?->toIso8601String(),
            'service_package' => $this->package_snapshot
                ?? ($this->servicePackage ? [
                    'id'               => $this->servicePackage->id,
                    'name'             => $this->servicePackage->name,
                    'type'             => $this->servicePackage->type instanceof \BackedEnum
                        ? $this->servicePackage->type->value
                        : $this->servicePackage->type,
                    'description'      => $this->servicePackage->description,
                    'cachet_amount'    => $this->servicePackage->cachet_amount,
                    'duration_minutes' => $this->servicePackage->duration_minutes,
                    'inclusions'       => $this->servicePackage->inclusions,
                ] : null),
            'event_date'      => $this->event_date->toDateString(),
            'start_time'      => $this->start_time,
            'event_location'  => $this->event_location,
            'message'         => $this->message,
            'is_express'                  => $this->is_express,
            'reject_reason'               => $this->reject_reason,
            'refund_amount'               => $this->refund_amount,
            'cancellation_policy_applied' => $this->cancellation_policy_applied,
            // Contract is available only after payment AND the PDF has been generated
            'contract_available' => $this->contract_path !== null
                && in_array($this->status, [
                    BookingStatus::Paid,
                    BookingStatus::Confirmed,
                    BookingStatus::Completed,
                ], true),
            'has_client_review'    => $this->reviews()->where('type', ReviewType::ClientToTalent->value)->exists(),
            'has_talent_review'    => $this->reviews()->where('type', ReviewType::TalentToClient->value)->exists(),
            'client_review_id'     => $this->reviews()->where('type', ReviewType::ClientToTalent->value)->value('id'),
            'client_review_reply'  => $this->reviews()->where('type', ReviewType::ClientToTalent->value)->value('reply'),
            'devis'           => [
                'cachet_amount'     => $this->cachet_amount,
                'travel_cost'       => $this->travel_cost,
                'express_fee'       => $this->express_fee,
                'commission_amount' => $this->commission_amount,
                'discount_amount'   => $this->discount_amount ?? 0,
                'promo_code'        => $this->promoCode?->code,
                'total_amount'      => $this->total_amount,
                'message'           => sprintf(
                    'Cachet artiste intact — BookMi ajoute %d%% de frais de service',
                    (int) config('bookmi.commission_rate', 15),
                ),
            ],
            // Only included when eager-loaded via ->load('statusLogs.performer')
            'history' => $this->whenLoaded(
                'statusLogs',
                fn () =>
                $this->statusLogs->map(fn ($log) => [
                    'id'           => $log->id,
                    'from_status'  => $log->from_status,
                    'to_status'    => $log->to_status,
                    'performed_by' => $log->performer ? [
                        'id'   => $log->performer->id,
                        'name' => trim($log->performer->first_name . ' ' . $log->performer->last_name),
                    ] : null,
                    'created_at'   => $log->created_at?->toIso8601String(),
                ])->values()
            ),
            // Pending reschedule (only available in detail view)
            'pending_reschedule' => $this->whenLoaded(
                'rescheduleRequests',
                function () {
                    $pending = $this->rescheduleRequests
                        ->first(fn ($r) => $r->status === RescheduleStatus::Pending);

                    if (! $pending) {
                        return null;
                    }

                    return [
                        'id'              => $pending->id,
                        'proposed_date'   => $pending->proposed_date->toDateString(),
                        'message'         => $pending->message,
                        'requested_by_id' => $pending->requested_by_id,
                        'status'          => $pending->status->value,
                        'created_at'      => $pending->created_at?->toIso8601String(),
                    ];
                },
            ),
        ];
    }
}
