<?php

namespace Database\Factories;

use App\Enums\CalendarSlotStatus;
use App\Models\CalendarSlot;
use App\Models\TalentProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CalendarSlot>
 */
class CalendarSlotFactory extends Factory
{
    protected $model = CalendarSlot::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'talent_profile_id' => TalentProfile::factory(),
            'date'              => fake()->dateTimeBetween('today', '+3 months')->format('Y-m-d'),
            'status'            => CalendarSlotStatus::Blocked,
        ];
    }

    public function available(): static
    {
        return $this->state(fn (): array => ['status' => CalendarSlotStatus::Available]);
    }

    public function blocked(): static
    {
        return $this->state(fn (): array => ['status' => CalendarSlotStatus::Blocked]);
    }

    public function rest(): static
    {
        return $this->state(fn (): array => ['status' => CalendarSlotStatus::Rest]);
    }
}
