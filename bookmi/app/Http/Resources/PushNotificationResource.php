<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\PushNotification */
class PushNotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'title'      => $this->title,
            'body'       => $this->body,
            'data'       => $this->data,
            'is_read'    => $this->isRead(),
            'read_at'    => $this->read_at?->toISOString(),
            'sent_at'    => $this->sent_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
