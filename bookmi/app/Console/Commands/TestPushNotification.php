<?php

namespace App\Console\Commands;

use App\Models\PushNotification;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Console\Command;

class TestPushNotification extends Command
{
    protected $signature = 'bookmi:test-push
                            {--user=  : User ID to notify (default: first admin or first user)}
                            {--token= : FCM device token (override stored token)}
                            {--type=booking_requested : Notification type}';

    protected $description = 'Send a test push notification to verify FCM integration';

    public function handle(FcmService $fcm): int
    {
        // ── Resolve target user ──────────────────────────────────────────────
        $userId = $this->option('user');
        $user   = $userId
            ? User::findOrFail($userId)
            : User::whereNotNull('fcm_token')->first() ?? User::first();

        if (! $user) {
            $this->error('No user found.');
            return self::FAILURE;
        }

        $this->info("Target user : [{$user->id}] {$user->name} <{$user->email}>");

        // ── Resolve FCM token ────────────────────────────────────────────────
        $token = $this->option('token') ?: $user->fcm_token;

        if (! $token) {
            $this->warn("User [{$user->id}] has no FCM token stored.");
            $this->warn('→ Log into the app (mobile or Filament admin) to register a token first.');
        }

        // ── Build notification payload ───────────────────────────────────────
        $type  = $this->option('type');
        $title = 'BookMi — Test notification';
        $body  = "Test envoyé le " . now()->format('d/m/Y H:i:s') . " • type: {$type}";
        $data  = ['type' => $type, 'booking_id' => '999'];

        // ── Save in-app record ───────────────────────────────────────────────
        $record = PushNotification::create([
            'user_id' => $user->id,
            'title'   => $title,
            'body'    => $body,
            'data'    => $data,
        ]);

        $this->info("In-app record created → ID {$record->id}");

        // ── Send push (only if token exists) ─────────────────────────────────
        if ($token) {
            $this->info("Sending FCM push to token: " . substr($token, 0, 20) . '…');
            $ok = $fcm->send($token, $title, $body, $data);

            if ($ok) {
                PushNotification::where('id', $record->id)->update(['sent_at' => now()]);
                $this->info('<fg=green>✓ FCM push sent successfully.</>');
            } else {
                $this->error('✗ FCM push failed — check logs (storage/logs/laravel.log).');
                return self::FAILURE;
            }
        } else {
            $this->warn('Skipping FCM push (no token). In-app record saved — visible on next Livewire poll.');
        }

        $this->newLine();
        $this->table(
            ['Field', 'Value'],
            [
                ['User',  "{$user->name} (#{$user->id})"],
                ['Token', $token ? substr($token, 0, 30) . '…' : '(none)'],
                ['Type',  $type],
                ['DB ID', $record->id],
            ]
        );

        return self::SUCCESS;
    }
}
