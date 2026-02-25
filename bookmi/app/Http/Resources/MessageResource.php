<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\Message */
class MessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender_id'       => $this->sender_id,
            'sender_name'     => $this->whenLoaded('sender', fn () => $this->sender->name),
            'content'         => $this->content,
            'type'            => $this->type->value,
            'media_url'       => $this->media_path
                                    ? Storage::disk('public')->url($this->media_path)
                                    : null,
            'media_type'      => $this->media_type,
            'is_flagged'      => $this->is_flagged,
            'is_auto_reply'   => $this->is_auto_reply,
            'read_at'         => $this->read_at?->toISOString(),
            'created_at'      => $this->created_at?->toISOString(),
        ];
    }
}
