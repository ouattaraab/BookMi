<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\TalentProfile
 */
class TalentProfileResource extends JsonResource
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
                'bio' => $this->bio,
                'city' => $this->city,
                'cachet_amount' => $this->cachet_amount,
                'social_links' => $this->social_links,
                'is_verified'     => $this->is_verified,
                'is_group'        => $this->is_group,
                'group_size'      => $this->group_size,
                'collective_name' => $this->collective_name,
                'talent_level' => $this->talent_level,
                'average_rating'             => $this->average_rating,
                'avg_punctuality_score'      => $this->avg_punctuality_score,
                'avg_quality_score'          => $this->avg_quality_score,
                'avg_professionalism_score'  => $this->avg_professionalism_score,
                'avg_contract_respect_score' => $this->avg_contract_respect_score,
                'total_bookings' => $this->total_bookings,
                'visibility_score' => round((float) $this->visibility_score, 1),
                'profile_completion_percentage' => $this->profile_completion_percentage,
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
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ],
            'managers' => $this->when(
                $this->relationLoaded('managers'),
                fn (): array => $this->managers
                    ->map(fn (User $m): array => [
                        'id'    => $m->id,
                        'name'  => $m->first_name . ' ' . $m->last_name,
                        'email' => $m->email,
                    ])
                    ->toArray(),
                [],
            ),
        ];
    }
}
