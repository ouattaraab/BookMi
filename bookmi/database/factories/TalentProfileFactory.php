<?php

namespace Database\Factories;

use App\Enums\TalentLevel;
use App\Models\Category;
use App\Models\TalentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TalentProfile>
 */
class TalentProfileFactory extends Factory
{
    protected $model = TalentProfile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'stage_name' => fake()->unique()->name(),
            'bio' => fake()->paragraph(),
            'city' => fake()->city(),
            'cachet_amount' => fake()->numberBetween(5000, 50000000),
            'latitude' => null,
            'longitude' => null,
            'social_links' => [
                'instagram' => 'https://instagram.com/' . fake()->userName(),
            ],
            'is_verified' => false,
            'talent_level' => TalentLevel::NOUVEAU,
            'average_rating' => 0,
            'total_bookings' => 0,
            'profile_completion_percentage' => 20,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (): array => [
            'is_verified' => true,
        ]);
    }

    public function withExpressBooking(): static
    {
        return $this->state(fn (): array => [
            'enable_express_booking' => true,
        ]);
    }

    public function nouveau(): static
    {
        return $this->state(fn (): array => [
            'talent_level' => TalentLevel::NOUVEAU,
            'total_bookings' => 0,
        ]);
    }

    public function confirme(): static
    {
        return $this->state(fn (): array => [
            'talent_level' => TalentLevel::CONFIRME,
            'total_bookings' => 10,
            'average_rating' => 3.80,
        ]);
    }

    public function populaire(): static
    {
        return $this->state(fn (): array => [
            'talent_level' => TalentLevel::POPULAIRE,
            'total_bookings' => 30,
            'average_rating' => 4.20,
        ]);
    }

    public function elite(): static
    {
        return $this->state(fn (): array => [
            'talent_level' => TalentLevel::ELITE,
            'total_bookings' => 60,
            'average_rating' => 4.70,
        ]);
    }

    public function withCoordinates(float $lat = 5.3600, float $lng = -4.0083): static
    {
        return $this->state(fn (): array => [
            'latitude' => $lat,
            'longitude' => $lng,
        ]);
    }

    public function inAbidjan(): static
    {
        return $this->state(fn (): array => [
            'latitude' => 5.3364,
            'longitude' => -3.9683,
        ]);
    }

    public function inBouake(): static
    {
        return $this->state(fn (): array => [
            'latitude' => 7.6939,
            'longitude' => -5.0308,
        ]);
    }
}
