<?php

namespace Tests\Unit\Commands;

use App\Enums\TalentLevel;
use App\Models\TalentProfile;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecalculateTalentLevelsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    #[Test]
    public function command_upgrades_talent_to_confirme(): void
    {
        $talent = TalentProfile::factory()->create([
            'talent_level' => TalentLevel::NOUVEAU,
            'total_bookings' => 8,
            'average_rating' => 3.8,
        ]);

        $this->artisan('bookmi:recalculate-talent-levels')
            ->assertExitCode(0);

        $this->assertDatabaseHas('talent_profiles', [
            'id' => $talent->id,
            'talent_level' => TalentLevel::CONFIRME->value,
        ]);
    }

    #[Test]
    public function command_upgrades_talent_to_populaire(): void
    {
        $talent = TalentProfile::factory()->create([
            'talent_level' => TalentLevel::CONFIRME,
            'total_bookings' => 25,
            'average_rating' => 4.2,
        ]);

        $this->artisan('bookmi:recalculate-talent-levels')
            ->assertExitCode(0);

        $this->assertDatabaseHas('talent_profiles', [
            'id' => $talent->id,
            'talent_level' => TalentLevel::POPULAIRE->value,
        ]);
    }

    #[Test]
    public function command_upgrades_talent_to_elite(): void
    {
        $talent = TalentProfile::factory()->create([
            'talent_level' => TalentLevel::POPULAIRE,
            'total_bookings' => 55,
            'average_rating' => 4.7,
        ]);

        $this->artisan('bookmi:recalculate-talent-levels')
            ->assertExitCode(0);

        $this->assertDatabaseHas('talent_profiles', [
            'id' => $talent->id,
            'talent_level' => TalentLevel::ELITE->value,
        ]);
    }

    #[Test]
    public function command_does_not_upgrade_when_rating_below_threshold(): void
    {
        $talent = TalentProfile::factory()->create([
            'talent_level' => TalentLevel::NOUVEAU,
            'total_bookings' => 10,
            'average_rating' => 3.0, // below 3.5 required for confirme
        ]);

        $this->artisan('bookmi:recalculate-talent-levels')
            ->assertExitCode(0);

        $this->assertDatabaseHas('talent_profiles', [
            'id' => $talent->id,
            'talent_level' => TalentLevel::NOUVEAU->value,
        ]);
    }

    #[Test]
    public function dry_run_does_not_save_changes(): void
    {
        $talent = TalentProfile::factory()->create([
            'talent_level' => TalentLevel::NOUVEAU,
            'total_bookings' => 10,
            'average_rating' => 4.0,
        ]);

        $this->artisan('bookmi:recalculate-talent-levels', ['--dry-run' => true])
            ->assertExitCode(0);

        $this->assertDatabaseHas('talent_profiles', [
            'id' => $talent->id,
            'talent_level' => TalentLevel::NOUVEAU->value,
        ]);
    }
}
