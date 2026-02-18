<?php

use App\Console\Commands\ReleaseExpiredEscrows;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto-release escrow holds after 48h (NFR33 — auto-confirm if client does not respond)
Schedule::command(ReleaseExpiredEscrows::class)->dailyAt('00:00');
