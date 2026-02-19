<?php

namespace Tests\Feature\Commands;

use App\Enums\BookingStatus;
use App\Enums\TrackingStatus;
use App\Enums\UserRole;
use App\Jobs\SendPushNotification;
use App\Models\BookingRequest;
use App\Models\TalentProfile;
use App\Models\TrackingEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendMissingCheckinAlertsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    }

    private function makeBookingForToday(BookingStatus $status = BookingStatus::Confirmed): BookingRequest
    {
        $client = User::factory()->create();
        $client->assignRole(UserRole::CLIENT->value);

        $talent  = User::factory()->create();
        $profile = TalentProfile::factory()->create(['user_id' => $talent->id]);

        return BookingRequest::factory()->create([
            'client_id'         => $client->id,
            'talent_profile_id' => $profile->id,
            'event_date'        => Carbon::today()->toDateString(),
            'status'            => $status,
        ]);
    }

    public function test_dispatches_alerts_for_bookings_without_checkin(): void
    {
        Queue::fake();

        $booking = $this->makeBookingForToday();

        $this->artisan('bookmi:missing-checkin-alerts')
            ->assertSuccessful();

        Queue::assertPushed(SendPushNotification::class, 2); // client + talent
    }

    public function test_skips_bookings_with_checkin(): void
    {
        Queue::fake();

        $booking = $this->makeBookingForToday();
        $talent  = User::factory()->create();

        TrackingEvent::factory()->create([
            'booking_request_id' => $booking->id,
            'updated_by'         => $talent->id,
            'status'             => TrackingStatus::Arrived,
        ]);

        $this->artisan('bookmi:missing-checkin-alerts')
            ->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_skips_bookings_with_other_statuses(): void
    {
        Queue::fake();

        $this->makeBookingForToday(BookingStatus::Pending);
        $this->makeBookingForToday(BookingStatus::Completed);
        $this->makeBookingForToday(BookingStatus::Cancelled);

        $this->artisan('bookmi:missing-checkin-alerts')
            ->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_skips_events_not_scheduled_today(): void
    {
        Queue::fake();

        $client  = User::factory()->create();
        $client->assignRole(UserRole::CLIENT->value);
        $profile = TalentProfile::factory()->create();

        BookingRequest::factory()->create([
            'client_id'         => $client->id,
            'talent_profile_id' => $profile->id,
            'event_date'        => Carbon::tomorrow()->toDateString(),
            'status'            => BookingStatus::Confirmed,
        ]);

        $this->artisan('bookmi:missing-checkin-alerts')
            ->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_dry_run_does_not_dispatch_jobs(): void
    {
        Queue::fake();

        $this->makeBookingForToday();

        $this->artisan('bookmi:missing-checkin-alerts', ['--dry-run' => true])
            ->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_paid_bookings_are_also_alerted(): void
    {
        Queue::fake();

        $this->makeBookingForToday(BookingStatus::Paid);

        $this->artisan('bookmi:missing-checkin-alerts')
            ->assertSuccessful();

        Queue::assertPushed(SendPushNotification::class, 2);
    }
}
