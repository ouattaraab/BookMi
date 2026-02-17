<?php

namespace Database\Factories;

use App\Enums\VerificationStatus;
use App\Models\IdentityVerification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IdentityVerification>
 */
class IdentityVerificationFactory extends Factory
{
    protected $model = IdentityVerification::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'document_type' => fake()->randomElement(['cni', 'passport']),
            'stored_path' => 'identity/' . fake()->uuid() . '.enc',
            'original_mime' => 'image/jpeg',
            'verification_status' => VerificationStatus::PENDING,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => VerificationStatus::PENDING,
            'reviewer_id' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
            'verified_at' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => VerificationStatus::APPROVED,
            'reviewer_id' => User::factory()->state(['is_admin' => true]),
            'reviewed_at' => now(),
            'rejection_reason' => null,
            'verified_at' => now(),
            'stored_path' => null,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => VerificationStatus::REJECTED,
            'reviewer_id' => User::factory()->state(['is_admin' => true]),
            'reviewed_at' => now(),
            'rejection_reason' => 'Document illisible.',
            'verified_at' => null,
            'stored_path' => null,
        ]);
    }
}
