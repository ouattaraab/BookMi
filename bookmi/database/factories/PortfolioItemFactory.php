<?php

namespace Database\Factories;

use App\Models\TalentProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PortfolioItem>
 */
class PortfolioItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'talent_profile_id'  => TalentProfile::factory(),
            'booking_request_id' => null,
            'media_type'         => 'image',
            'original_path'      => 'uploads/portfolio/' . fake()->uuid() . '.jpg',
            'compressed_path'    => null,
            'caption'            => fake()->optional()->sentence(),
            'is_compressed'      => false,
        ];
    }
}
