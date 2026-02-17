<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\TalentProfile
 */
class TalentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'talent_profile',
            'attributes' => [
                'stage_name' => $this->stage_name,
                'slug' => $this->slug,
                'city' => $this->city,
                'cachet_amount' => $this->cachet_amount,
                'average_rating' => $this->average_rating,
                'is_verified' => $this->is_verified,
                'talent_level' => $this->talent_level,
                'category' => [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                    'color_hex' => $this->category->color_hex,
                ],
                'subcategory' => $this->when($this->subcategory_id !== null, fn () => [
                    'id' => $this->subcategory?->id,
                    'name' => $this->subcategory?->name,
                    'slug' => $this->subcategory?->slug,
                ]),
                'distance_km' => $this->when(
                    $this->resource->getAttribute('distance_km') !== null,
                    fn () => round((float) $this->resource->getAttribute('distance_km'), 2),
                ),
            ],
        ];
    }
}
