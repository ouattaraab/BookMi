<?php

namespace Database\Factories;

use App\Models\PushNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PushNotification>
 */
class PushNotificationFactory extends Factory
{
    protected $model = PushNotification::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title'   => fake()->sentence(4),
            'body'    => fake()->sentence(),
            'data'    => null,
            'read_at' => null,
            'sent_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn (): array => ['read_at' => now()]);
    }

    public function sent(): static
    {
        return $this->state(fn (): array => ['sent_at' => now()]);
    }
}
