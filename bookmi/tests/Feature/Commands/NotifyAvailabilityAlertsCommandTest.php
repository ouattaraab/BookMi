<?php

namespace Tests\Feature\Commands;

use App\Enums\BookingStatus;
use App\Jobs\SendPushNotification;
use App\Models\AvailabilityAlert;
use App\Models\BookingRequest;
use App\Models\TalentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotifyAvailabilityAlertsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifies_user_when_slot_is_free(): void
    {
        Queue::fake();

        $user   = User::factory()->create();
        $talent = TalentProfile::factory()->create();

        $alert = AvailabilityAlert::create([
            'user_id'           => $user->id,
            'talent_profile_id' => $talent->id,
            'event_date'        => today()->addDays(5)->toDateString(),
        ]);

        $this->artisan('bookmi:notify-availability-alerts')
            ->assertExitCode(0);

        Queue::assertPushed(SendPushNotification::class, function ($job) use ($user) {
            return $job->userId === $user->id;
        });

        $this->assertNotNull($alert->fresh()->notified_at);
    }

    public function test_does_not_notify_when_slot_is_busy(): void
    {
        Queue::fake();

        $user   = User::factory()->create();
        $talent = TalentProfile::factory()->create();
        $eventDate = today()->addDays(5);

        BookingRequest::factory()->create([
            'talent_profile_id' => $talent->id,
            'status'            => BookingStatus::Confirmed->value,
            'event_date'        => $eventDate->toDateString(),
        ]);

        AvailabilityAlert::create([
            'user_id'           => $user->id,
            'talent_profile_id' => $talent->id,
            'event_date'        => $eventDate->toDateString(),
        ]);

        $this->artisan('bookmi:notify-availability-alerts')
            ->assertExitCode(0);

        Queue::assertNotPushed(SendPushNotification::class);
    }

    public function test_skips_already_notified_alerts(): void
    {
        Queue::fake();

        $user   = User::factory()->create();
        $talent = TalentProfile::factory()->create();

        AvailabilityAlert::create([
            'user_id'           => $user->id,
            'talent_profile_id' => $talent->id,
            'event_date'        => today()->addDays(5)->toDateString(),
            'notified_at'       => now()->subHour(),
        ]);

        $this->artisan('bookmi:notify-availability-alerts')
            ->assertExitCode(0);

        Queue::assertNotPushed(SendPushNotification::class);
    }

    public function test_skips_past_event_dates(): void
    {
        Queue::fake();

        $user   = User::factory()->create();
        $talent = TalentProfile::factory()->create();

        // Insert directly to bypass model validation (past date)
        \Illuminate\Support\Facades\DB::table('availability_alerts')->insert([
            'user_id'           => $user->id,
            'talent_profile_id' => $talent->id,
            'event_date'        => today()->subDay()->toDateString(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $this->artisan('bookmi:notify-availability-alerts')
            ->assertExitCode(0);

        Queue::assertNotPushed(SendPushNotification::class);
    }

    public function test_dry_run_does_not_dispatch_or_mark(): void
    {
        Queue::fake();

        $user   = User::factory()->create();
        $talent = TalentProfile::factory()->create();

        $alert = AvailabilityAlert::create([
            'user_id'           => $user->id,
            'talent_profile_id' => $talent->id,
            'event_date'        => today()->addDays(3)->toDateString(),
        ]);

        $this->artisan('bookmi:notify-availability-alerts --dry-run')
            ->assertExitCode(0);

        Queue::assertNotPushed(SendPushNotification::class);
        $this->assertNull($alert->fresh()->notified_at);
    }
}
