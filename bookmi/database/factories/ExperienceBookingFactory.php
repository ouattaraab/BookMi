<?php

namespace Database\Factories;

use App\Enums\ExperienceBookingStatus;
use App\Models\ExperienceBooking;
use App\Models\PrivateExperience;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExperienceBooking>
 */
class ExperienceBookingFactory extends Factory
{
    protected $model = ExperienceBooking::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $seats            = fake()->numberBetween(1, 5);
        $pricePerSeat     = fake()->numberBetween(10_000, 100_000);
        $totalAmount      = $pricePerSeat * $seats;
        $commissionAmount = (int) round($totalAmount * 0.15);

        return [
            'private_experience_id' => PrivateExperience::factory(),
            'client_id'             => User::factory(),
            'seats_count'           => $seats,
            'price_per_seat'        => $pricePerSeat,
            'total_amount'          => $totalAmount,
            'commission_amount'     => $commissionAmount,
            'status'                => ExperienceBookingStatus::Pending,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(['status' => ExperienceBookingStatus::Confirmed]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status'       => ExperienceBookingStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }
}
