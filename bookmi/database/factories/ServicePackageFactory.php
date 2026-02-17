<?php

namespace Database\Factories;

use App\Enums\PackageType;
use App\Models\ServicePackage;
use App\Models\TalentProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServicePackage>
 */
class ServicePackageFactory extends Factory
{
    protected $model = ServicePackage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'talent_profile_id' => TalentProfile::factory(),
            'name' => fake()->randomElement(['Essentiel', 'Standard', 'Premium']),
            'description' => fake()->sentence(),
            'cachet_amount' => fake()->numberBetween(5000000, 50000000),
            'duration_minutes' => fake()->numberBetween(60, 240),
            'inclusions' => null,
            'type' => PackageType::Essentiel,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function micro(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PackageType::Micro,
            'duration_minutes' => null,
            'name' => fake()->randomElement(['Vidéo personnalisée', 'Dédicace audio']),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PackageType::Premium,
            'name' => 'Premium',
            'cachet_amount' => fake()->numberBetween(20000000, 50000000),
        ]);
    }

    public function standard(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PackageType::Standard,
            'name' => 'Standard',
            'cachet_amount' => fake()->numberBetween(10000000, 20000000),
        ]);
    }
}
