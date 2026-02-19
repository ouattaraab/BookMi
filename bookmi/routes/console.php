<?php

use App\Console\Commands\DetectTalentOverload;
use App\Console\Commands\RecalculateTalentLevels;
use App\Console\Commands\ReleaseExpiredEscrows;
use App\Console\Commands\SendMissingCheckinAlerts;
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

// Missing check-in alerts for events happening today (Story 6.3)
Schedule::command(SendMissingCheckinAlerts::class)->dailyAt('10:00');

// Recalculate talent levels daily (Story 7.7)
Schedule::command(RecalculateTalentLevels::class)->dailyAt('02:00');

// Detect overloaded talents and notify managers (Story 7.3)
Schedule::command(DetectTalentOverload::class)->dailyAt('09:00');
