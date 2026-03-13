<?php

namespace Database\Factories;

use App\Enums\ExperienceStatus;
use App\Models\PrivateExperience;
use App\Models\TalentProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrivateExperience>
 */
class PrivateExperienceFactory extends Factory
{
    protected $model = PrivateExperience::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $maxSeats   = fake()->numberBetween(5, 30);
        $totalPrice = fake()->numberBetween(100_000, 2_000_000);

        return [
            'talent_profile_id' => TalentProfile::factory(),
            'title'             => 'Evening privé avec ' . fake()->name(),
            'description'       => fake()->paragraph(),
            'event_date'        => fake()->dateTimeBetween('tomorrow', '+3 months'),
            'venue_address'     => fake()->address(),
            'venue_revealed'    => false,
            'total_price'       => $totalPrice,
            'max_seats'         => $maxSeats,
            'booked_seats'      => 0,
            'status'            => ExperienceStatus::Published,
            'premium_options'   => null,
            'commission_rate'   => 15,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => ExperienceStatus::Draft]);
    }

    public function full(): static
    {
        return $this->state(fn (array $attrs) => [
            'status'       => ExperienceStatus::Full,
            'booked_seats' => $attrs['max_seats'],
        ]);
    }

    public function past(): static
    {
        return $this->state([
            'event_date' => fake()->dateTimeBetween('-3 months', '-1 day'),
        ]);
    }
}
