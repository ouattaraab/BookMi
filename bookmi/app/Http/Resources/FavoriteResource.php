<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\TalentProfile
 *
 * @property \Illuminate\Database\Eloquent\Relations\Pivot $pivot
 */
class FavoriteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $pivot = $this->resource->pivot ?? null;

        return [
            'id' => $pivot?->id,
            'type' => 'favorite',
            'attributes' => [
                'talent' => new TalentResource($this->resource),
                'favorited_at' => $pivot?->getAttribute('created_at')?->toIso8601String(),
            ],
        ];
    }
}
