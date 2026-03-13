<?php

namespace App\Console\Commands;

use App\Enums\ExperienceBookingStatus;
use App\Enums\ExperienceStatus;
use App\Jobs\SendPushNotification;
use App\Models\ExperienceBooking;
use App\Models\PrivateExperience;
use Illuminate\Console\Command;

class SendMeetAndGreetReminders extends Command
{
    protected $signature = 'bookmi:send-mg-reminders';

    protected $description = 'Send FCM push reminders to clients whose Meet & Greet event is happening tomorrow.';

    public function handle(): int
    {
        $count = 0;

        PrivateExperience::whereIn('status', [
            ExperienceStatus::Published->value,
            ExperienceStatus::Full->value,
        ])
            ->whereNull('reminder_sent_at')
            ->whereBetween('event_date', [now(), now()->addDay()])
            ->chunkById(100, function ($experiences) use (&$count) {
                foreach ($experiences as $experience) {
                    /** @var PrivateExperience $experience */
                    $bookings = ExperienceBooking::where('private_experience_id', $experience->id)
                        ->where('status', ExperienceBookingStatus::Confirmed->value)
                        ->get();

                    foreach ($bookings as $booking) {
                        SendPushNotification::dispatch(
                            $booking->client_id,
                            'Meet & Greet demain 🎉',
                            "Rendez-vous demain pour \"{$experience->title}\" !",
                            [
                                'type'          => 'mg_reminder',
                                'experience_id' => (string) $experience->id,
                            ],
                        );
                        $count++;
                    }

                    $experience->update(['reminder_sent_at' => now()]);
                    $this->line("Experience #{$experience->id} — {$bookings->count()} reminder(s) dispatched.");
                }
            });

        $this->info("{$count} M&G reminder(s) dispatched.");

        return self::SUCCESS;
    }
}
