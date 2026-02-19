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
            'client'             => $this->whenLoaded('client', fn () => [
                'id'   => $this->client->id,
                'name' => $this->client->name,
            ]),
            'talent'             => $this->whenLoaded('talentProfile', fn () => [
                'id'   => $this->talentProfile->id,
                'name' => $this->talentProfile->user?->name,
            ]),
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
