<?php

namespace Tests\Unit\Commands;

use App\Enums\AlertType;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DetectSuspiciousActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    #[Test]
    public function command_detects_duplicate_phone_prefix(): void
    {
        // Two users sharing the same phone prefix
        User::factory()->create(['phone' => '+2250700000001']);
        User::factory()->create(['phone' => '+2250700000002']);

        $this->artisan('bookmi:detect-suspicious-activity')->assertExitCode(0);

        $this->assertDatabaseHas('admin_alerts', [
            'type' => AlertType::SuspiciousActivity->value,
        ]);
    }

    #[Test]
    public function command_does_not_flag_unique_phone_prefixes(): void
    {
        User::factory()->create(['phone' => '+2250711111111']);
        User::factory()->create(['phone' => '+2250722222222']);

        $this->artisan('bookmi:detect-suspicious-activity')->assertExitCode(0);

        $this->assertDatabaseCount('admin_alerts', 0);
    }

    #[Test]
    public function dry_run_does_not_create_alerts(): void
    {
        User::factory()->create(['phone' => '+2250700000001']);
        User::factory()->create(['phone' => '+2250700000002']);

        $this->artisan('bookmi:detect-suspicious-activity', ['--dry-run' => true])->assertExitCode(0);

        $this->assertDatabaseCount('admin_alerts', 0);
    }
}
