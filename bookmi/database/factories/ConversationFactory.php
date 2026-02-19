<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\TalentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id'          => User::factory(),
            'talent_profile_id'  => TalentProfile::factory(),
            'booking_request_id' => null,
            'last_message_at'    => now(),
        ];
    }
}
