<?php

namespace Tests\Unit\Commands;

use App\Enums\AlertType;
use App\Models\AdminAlert;
use App\Models\TalentProfile;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FlagLowRatingTalentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    #[Test]
    public function command_creates_alert_for_low_rated_talent(): void
    {
        TalentProfile::factory()->create([
            'average_rating' => 2.5,
            'total_bookings' => 5,
        ]);

        $this->artisan('bookmi:flag-low-rating-talents')->assertExitCode(0);

        $this->assertDatabaseCount('admin_alerts', 1);
        $this->assertDatabaseHas('admin_alerts', ['type' => AlertType::LowRating->value]);
    }

    #[Test]
    public function command_does_not_flag_talent_with_good_rating(): void
    {
        TalentProfile::factory()->create([
            'average_rating' => 4.5,
            'total_bookings' => 10,
        ]);

        $this->artisan('bookmi:flag-low-rating-talents')->assertExitCode(0);

        $this->assertDatabaseCount('admin_alerts', 0);
    }

    #[Test]
    public function command_does_not_flag_new_talent_with_zero_rating(): void
    {
        TalentProfile::factory()->create([
            'average_rating' => 0,
            'total_bookings' => 0,
        ]);

        $this->artisan('bookmi:flag-low-rating-talents')->assertExitCode(0);

        $this->assertDatabaseCount('admin_alerts', 0);
    }

    #[Test]
    public function command_skips_if_open_alert_already_exists(): void
    {
        $talent = TalentProfile::factory()->create([
            'average_rating' => 2.0,
            'total_bookings' => 3,
        ]);

        // Run twice
        $this->artisan('bookmi:flag-low-rating-talents')->assertExitCode(0);
        $this->artisan('bookmi:flag-low-rating-talents')->assertExitCode(0);

        // Should only have one alert (second run skipped)
        $this->assertDatabaseCount('admin_alerts', 1);
    }

    #[Test]
    public function dry_run_does_not_create_alerts(): void
    {
        TalentProfile::factory()->create([
            'average_rating' => 2.0,
            'total_bookings' => 3,
        ]);

        $this->artisan('bookmi:flag-low-rating-talents', ['--dry-run' => true])->assertExitCode(0);

        $this->assertDatabaseCount('admin_alerts', 0);
    }
}
