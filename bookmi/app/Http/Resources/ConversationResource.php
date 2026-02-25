<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Conversation */
class ConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'client_id'          => $this->client_id,
            'talent_profile_id'  => $this->talent_profile_id,
            'booking_request_id' => $this->booking_request_id,
            'last_message_at'    => $this->last_message_at?->toISOString(),
            'unread_count'       => $this->unread_count ?? 0,

            'client'             => $this->whenLoaded('client', fn () => [
                'id'         => $this->client->id,
                'name'       => $this->client->name,
                'avatar_url' => $this->client->avatar_url,
            ]),

            'talent'             => $this->whenLoaded('talentProfile', fn () => [
                'id'         => $this->talentProfile->id,
                'name'       => $this->talentProfile->user?->name,
                'avatar_url' => $this->talentProfile->user?->avatar_url
                                ?? $this->talentProfile->cover_photo_url,
            ]),

            'booking'            => $this->whenLoaded('bookingRequest', function () {
                if (! $this->bookingRequest) {
                    return null;
                }
                $b        = $this->bookingRequest;
                $snapshot = $b->package_snapshot ?? [];
                $closedStatuses = ['completed', 'cancelled'];
                return [
                    'id'             => $b->id,
                    'title'          => $snapshot['name'] ?? null,
                    'event_date'     => $b->event_date?->toDateString(),
                    'event_location' => $b->event_location,
                    'status'         => $b->status->value,
                    'is_closed'      => in_array($b->status->value, $closedStatuses, true),
                ];
            }),

            'latest_message'     => $this->whenLoaded(
                'latestMessage',
                fn () => $this->latestMessage
                    ? new MessageResource($this->latestMessage)
                    : null,
            ),

            'created_at'         => $this->created_at?->toISOString(),
        ];
    }
}
