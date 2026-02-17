<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\TalentProfile
 */
class TalentDetailResource extends JsonResource
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
                'average_rating' => $this->average_rating,
                'is_verified' => $this->is_verified,
                'talent_level' => $this->talent_level,
                'profile_completion_percentage' => $this->profile_completion_percentage,
                'social_links' => $this->social_links,
                'reliability_score' => $this->calculateReliabilityScore(),
                'reviews_count' => 0,
                'portfolio_items' => [],
                'service_packages' => ServicePackageResource::collection($this->whenLoaded('servicePackages')),
                'recent_reviews' => [],
                'created_at' => $this->created_at?->toIso8601String(),
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
            ],
        ];
    }

    private function calculateReliabilityScore(): int
    {
        $score = 0;
        $score += $this->is_verified ? 30 : 0;
        $score += min(30, (int) round((float) $this->average_rating * 6));
        $score += min(20, $this->total_bookings);
        $score += min(20, (int) ($this->profile_completion_percentage * 0.2));

        return $score;
    }
}
