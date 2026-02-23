<?php

namespace Tests\Feature\Commands;

use App\Enums\BookingStatus;
use App\Jobs\SendPushNotification;
use App\Models\BookingRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendReminderNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    private function bookingInDays(int $days, string $status = 'paid'): BookingRequest
    {
        return BookingRequest::factory()->{$status}()->create([
            'event_date' => Carbon::today()->addDays($days)->toDateString(),
        ]);
    }

    public function test_dispatches_jobs_for_j7_bookings(): void
    {
        $booking = $this->bookingInDays(7);

        $this->artisan('bookmi:send-reminders')
            ->assertExitCode(0);

        Queue::assertPushed(
            SendPushNotification::class,
            fn ($job) =>
            $job->userId === $booking->client_id
        );
    }

    public function test_dispatches_jobs_for_j2_bookings(): void
    {
        $booking = $this->bookingInDays(2);

        $this->artisan('bookmi:send-reminders')
            ->assertExitCode(0);

        Queue::assertPushed(
            SendPushNotification::class,
            fn ($job) =>
            $job->userId === $booking->client_id
        );
    }

    public function test_does_not_dispatch_for_non_upcoming_bookings(): void
    {
        // J-1 (tomorrow) — not in the J-7 / J-2 window
        $this->bookingInDays(1);

        $this->artisan('bookmi:send-reminders')->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    public function test_does_not_dispatch_for_cancelled_bookings(): void
    {
        BookingRequest::factory()->create([
            'status'     => BookingStatus::Cancelled,
            'event_date' => Carbon::today()->addDays(7)->toDateString(),
        ]);

        $this->artisan('bookmi:send-reminders')->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    public function test_dry_run_does_not_dispatch_jobs(): void
    {
        $this->bookingInDays(7);
        $this->bookingInDays(2);

        $this->artisan('bookmi:send-reminders --dry-run')->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    public function test_job_payload_contains_booking_id(): void
    {
        $booking = $this->bookingInDays(7);

        $this->artisan('bookmi:send-reminders')->assertExitCode(0);

        Queue::assertPushed(
            SendPushNotification::class,
            fn ($job) =>
            ($job->data['booking_id'] ?? null) === (string) $booking->id
        );
    }
}
