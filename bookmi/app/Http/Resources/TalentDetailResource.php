<?php

namespace App\Http\Resources;

use App\Models\PortfolioItem;
use App\Models\Review;
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
                'photo_url' => $this->cover_photo_url,
                'bio' => $this->bio,
                'city' => $this->city,
                'cachet_amount' => $this->cachet_amount,
                'average_rating' => $this->average_rating,
                'is_verified' => $this->is_verified,
                'talent_level' => $this->talent_level,
                'profile_completion_percentage' => $this->profile_completion_percentage,
                'social_links' => $this->social_links,
                'reliability_score' => $this->calculateReliabilityScore(),
                'reviews_count' => $this->whenLoaded('receivedReviews', fn () => $this->receivedReviews->count(), 0),
                'portfolio_items' => $this->whenLoaded('portfolioItems', fn () => $this->portfolioItems->map(fn (PortfolioItem $item) => [
                    'url'           => $item->publicUrl(),
                    'media_type'    => $item->media_type,
                    'caption'       => $item->caption,
                    'link_url'      => $item->link_url,
                    'link_platform' => $item->link_platform,
                ])->values()->all(), []),
                'service_packages' => ServicePackageResource::collection($this->whenLoaded('servicePackages')),
                'recent_reviews' => $this->whenLoaded('receivedReviews', fn () => $this->receivedReviews->map(fn (Review $review) => [
                    'id'            => $review->id,
                    'reviewer_name' => $review->reviewer !== null
                        ? trim($review->reviewer->first_name . ' ' . $review->reviewer->last_name)
                        : 'Anonyme',
                    'rating'        => $review->rating,
                    'comment'       => $review->comment,
                    'reply'         => $review->reply,
                    'reply_at'      => $review->reply_at instanceof \Carbon\Carbon ? $review->reply_at->format('d/m/Y') : null,
                    'created_at'    => $review->created_at?->format('d/m/Y'),
                ])->values()->all(), []),
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
