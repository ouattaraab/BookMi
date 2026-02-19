<?php

use App\Console\Commands\ReleaseExpiredEscrows;
use App\Console\Commands\SendReminderNotifications;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto-release escrow holds after 48h (NFR33 — auto-confirm if client does not respond)
Schedule::command(ReleaseExpiredEscrows::class)->dailyAt('00:00');

// Reminder push notifications J-7 and J-2 before booking events (Story 5.5)
Schedule::command(SendReminderNotifications::class)->dailyAt('08:00');
