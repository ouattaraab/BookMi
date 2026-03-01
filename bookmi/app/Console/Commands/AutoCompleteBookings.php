<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\BookingRequest;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoCompleteBookings extends Command
{
    protected $signature = 'bookmi:auto-complete-bookings {--dry-run : Show what would be completed without saving}';

    protected $description = 'Auto-complete confirmed bookings where event_date passed 7+ days ago';

    public function __construct(private readonly BookingService $bookingService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $cutoff = Carbon::today()->subDays(7);
        $isDryRun = (bool) $this->option('dry-run');

        $query = BookingRequest::where('status', BookingStatus::Confirmed->value)
            ->where('event_date', '<=', $cutoff->toDateString())
            ->with('talentProfile');

        $count = $query->count();

        if ($count === 0) {
            $this->info('No bookings to auto-complete.');

            return self::SUCCESS;
        }

        $this->info("Found {$count} booking(s) to auto-complete (event_date <= {$cutoff->toDateString()}).");

        if ($isDryRun) {
            $query->each(function (BookingRequest $booking) {
                $this->line("  [dry-run] Would complete booking #{$booking->id} (event: {$booking->event_date})");
            });

            return self::SUCCESS;
        }

        $completed = 0;
        $query->each(function (BookingRequest $booking) use (&$completed) {
            try {
                $this->bookingService->markCompleted($booking);
                $completed++;
                $this->line("  ✓ Completed booking #{$booking->id}");
            } catch (\Throwable $e) {
                $this->error("  ✗ Failed booking #{$booking->id}: {$e->getMessage()}");
            }
        });

        $this->info("Auto-completed {$completed}/{$count} bookings.");

        return self::SUCCESS;
    }
}
