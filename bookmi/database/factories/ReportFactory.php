<?php

namespace Database\Factories;

use App\Enums\ReportReason;
use App\Models\BookingRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Report>
 */
class ReportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_request_id' => BookingRequest::factory(),
            'reporter_id'        => User::factory(),
            'reason'             => ReportReason::Other,
            'description'        => fake()->optional()->sentence(),
            'status'             => 'pending',
        ];
    }
}
