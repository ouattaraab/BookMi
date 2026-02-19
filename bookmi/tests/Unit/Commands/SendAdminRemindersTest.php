<?php

namespace Tests\Unit\Commands;

use App\Enums\AlertType;
use App\Enums\BookingStatus;
use App\Enums\VerificationStatus;
use App\Models\BookingRequest;
use App\Models\IdentityVerification;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendAdminRemindersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    #[Test]
    public function command_creates_alert_for_old_pending_verification(): void
    {
        $user         = User::factory()->create();
        $verification = IdentityVerification::factory()->create([
            'user_id'             => $user->id,
            'verification_status' => VerificationStatus::PENDING,
        ]);
        DB::table('identity_verifications')->where('id', $verification->id)->update([
            'created_at' => now()->subHours(72),
        ]);

        $this->artisan('bookmi:send-admin-reminders')->assertExitCode(0);

        $this->assertDatabaseHas('admin_alerts', [
            'type' => AlertType::PendingAction->value,
        ]);
    }

    #[Test]
    public function command_does_not_create_alert_for_recent_verification(): void
    {
        $user = User::factory()->create();
        IdentityVerification::factory()->create([
            'user_id'             => $user->id,
            'verification_status' => VerificationStatus::PENDING,
        ]);

        $this->artisan('bookmi:send-admin-reminders')->assertExitCode(0);

        $this->assertDatabaseCount('admin_alerts', 0);
    }

    #[Test]
    public function command_creates_alert_for_unresolved_old_dispute(): void
    {
        $booking = BookingRequest::factory()->create(['status' => BookingStatus::Disputed]);
        DB::table('booking_requests')->where('id', $booking->id)->update([
            'updated_at' => now()->subHours(72),
        ]);

        $this->artisan('bookmi:send-admin-reminders')->assertExitCode(0);

        $this->assertDatabaseHas('admin_alerts', [
            'type' => AlertType::PendingAction->value,
        ]);
    }

    #[Test]
    public function dry_run_does_not_create_alerts(): void
    {
        $user         = User::factory()->create();
        $verification = IdentityVerification::factory()->create([
            'user_id'             => $user->id,
            'verification_status' => VerificationStatus::PENDING,
        ]);
        DB::table('identity_verifications')->where('id', $verification->id)->update([
            'created_at' => now()->subHours(72),
        ]);

        $this->artisan('bookmi:send-admin-reminders', ['--dry-run' => true])->assertExitCode(0);

        $this->assertDatabaseCount('admin_alerts', 0);
    }
}
