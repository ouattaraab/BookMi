<?php

namespace Database\Factories;

use App\Enums\TrackingStatus;
use App\Models\BookingRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TrackingEvent>
 */
class TrackingEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_request_id' => BookingRequest::factory(),
            'updated_by'         => User::factory(),
            'status'             => TrackingStatus::Preparing,
            'latitude'           => fake()->latitude(-5.5, 10.5),
            'longitude'          => fake()->longitude(-8.5, 3.5),
            'occurred_at'        => now(),
        ];
    }
}
