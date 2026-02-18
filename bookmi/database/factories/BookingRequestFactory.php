<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\BookingRequest;
use App\Models\ServicePackage;
use App\Models\TalentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingRequest>
 */
class BookingRequestFactory extends Factory
{
    protected $model = BookingRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cachetAmount     = fake()->numberBetween(5_000_000, 50_000_000);
        $commissionAmount = (int) round($cachetAmount * 0.15);

        return [
            'client_id'          => User::factory(),
            'talent_profile_id'  => TalentProfile::factory(),
            'service_package_id' => ServicePackage::factory(),
            'event_date'         => fake()->dateTimeBetween('tomorrow', '+3 months')->format('Y-m-d'),
            'event_location'     => fake()->city(),
            'message'            => fake()->optional()->sentence(),
            'status'             => BookingStatus::Pending,
            'cachet_amount'      => $cachetAmount,
            'commission_amount'  => $commissionAmount,
            'total_amount'       => $cachetAmount + $commissionAmount,
        ];
    }

    /**
     * Ensure service_package belongs to the talent_profile.
     * Without this, talent_profile_id and service_package_id are independent
     * and the service_package does not belong to the talent — violating the business rule.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (BookingRequest $booking) {
            $alreadyLinked = ServicePackage::where('id', $booking->service_package_id)
                ->where('talent_profile_id', $booking->talent_profile_id)
                ->exists();

            if (! $alreadyLinked) {
                $package = ServicePackage::factory()->create([
                    'talent_profile_id' => $booking->talent_profile_id,
                    'cachet_amount'     => $booking->cachet_amount,
                ]);
                $booking->service_package_id = $package->id;
                $booking->saveQuietly();
            }
        });
    }

    public function pending(): static
    {
        return $this->state(['status' => BookingStatus::Pending]);
    }

    public function accepted(): static
    {
        return $this->state(['status' => BookingStatus::Accepted]);
    }

    public function paid(): static
    {
        return $this->state(['status' => BookingStatus::Paid]);
    }

    public function confirmed(): static
    {
        return $this->state(['status' => BookingStatus::Confirmed]);
    }

    public function completed(): static
    {
        return $this->state(['status' => BookingStatus::Completed]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => BookingStatus::Cancelled]);
    }

    public function disputed(): static
    {
        return $this->state(['status' => BookingStatus::Disputed]);
    }
}
