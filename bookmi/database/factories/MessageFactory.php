<?php

namespace Database\Factories;

use App\Enums\MessageType;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'sender_id'       => User::factory(),
            'content'         => fake()->sentence(),
            'type'            => MessageType::Text,
            'read_at'         => null,
            'is_flagged'      => false,
            'is_auto_reply'   => false,
        ];
    }

    public function read(): static
    {
        return $this->state(fn (): array => ['read_at' => now()]);
    }

    public function flagged(): static
    {
        return $this->state(fn (): array => ['is_flagged' => true]);
    }

    public function autoReply(): static
    {
        return $this->state(fn (): array => ['is_auto_reply' => true]);
    }
}
