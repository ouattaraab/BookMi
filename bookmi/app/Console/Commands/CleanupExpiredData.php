<?php

namespace App\Console\Commands;

use App\Models\BookingStatusLog;
use App\Models\PushNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CleanupExpiredData extends Command
{
    protected $signature = 'bookmi:cleanup-expired-data {--dry-run : Log only, do not delete}';

    protected $description = 'Delete old read push notifications and booking status logs.';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        // 1 — Push notifications lues depuis +30 jours
        $cutoffNotifications = Carbon::now()->subDays(30);
        $notifQuery          = PushNotification::whereNotNull('read_at')
            ->where('read_at', '<', $cutoffNotifications);

        $notifCount = $notifQuery->count();

        if (! $isDryRun) {
            $notifQuery->delete();
        }

        $this->info("{$notifCount} push notification(s) read 30+ days ago "
            . ($isDryRun ? '[DRY-RUN — not deleted]' : 'deleted') . '.');

        // 2 — BookingStatusLog de +1 an
        $cutoffLogs = Carbon::now()->subYear();
        $logsQuery  = BookingStatusLog::where('created_at', '<', $cutoffLogs);
        $logsCount  = $logsQuery->count();

        if (! $isDryRun) {
            $logsQuery->delete();
        }

        $this->info("{$logsCount} booking status log(s) older than 1 year "
            . ($isDryRun ? '[DRY-RUN — not deleted]' : 'deleted') . '.');

        $this->info('Cleanup done.');

        return self::SUCCESS;
    }
}
