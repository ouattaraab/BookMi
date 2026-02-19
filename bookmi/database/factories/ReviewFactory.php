<?php

namespace Database\Factories;

use App\Enums\ReviewType;
use App\Models\BookingRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_request_id' => BookingRequest::factory(),
            'reviewer_id'        => User::factory(),
            'reviewee_id'        => User::factory(),
            'type'               => ReviewType::ClientToTalent,
            'rating'             => fake()->numberBetween(1, 5),
            'comment'            => fake()->optional()->sentence(),
        ];
    }
}
