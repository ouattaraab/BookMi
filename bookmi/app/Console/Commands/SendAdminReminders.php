<?php

namespace App\Console\Commands;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use App\Enums\BookingStatus;
use App\Enums\VerificationStatus;
use App\Models\AdminAlert;
use App\Models\BookingRequest;
use App\Models\IdentityVerification;
use App\Models\Review;
use App\Services\AlertService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendAdminReminders extends Command
{
    protected $signature   = 'bookmi:send-admin-reminders {--dry-run : Log without creating alerts}';
    protected $description = 'Create pending-action alerts for overdue admin tasks (Story 8.11)';

    public function __construct(private readonly AlertService $alerts)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $isDryRun    = $this->option('dry-run');
        $reminderAt  = (int) config('bookmi.admin.pending_action_reminder_hours', 48);
        $threshold   = Carbon::now()->subHours($reminderAt);

        $this->info("Checking pending admin actions older than {$reminderAt}h…");

        // Pending identity verifications
        $pendingVerifications = IdentityVerification::where('verification_status', VerificationStatus::PENDING)
            ->where('created_at', '<', $threshold)
            ->get();

        foreach ($pendingVerifications as $verification) {
            if ($this->alerts->openExists(AlertType::PendingAction, $verification)) {
                continue;
            }
            $this->warn("Pending verification #{$verification->id} — {$verification->created_at}");
            if (! $isDryRun) {
                $this->alerts->create(
                    type: AlertType::PendingAction,
                    severity: AlertSeverity::Warning,
                    title: 'Vérification identité en attente',
                    description: "La vérification d'identité #{$verification->id} attend depuis plus de {$reminderAt}h.",
                    subject: $verification,
                    metadata: ['hours_pending' => Carbon::parse($verification->created_at)->diffInHours()],
                );
            }
        }

        // Open disputes older than threshold
        $openDisputes = BookingRequest::where('status', BookingStatus::Disputed)
            ->where('updated_at', '<', $threshold)
            ->get();

        foreach ($openDisputes as $booking) {
            if ($this->alerts->openExists(AlertType::PendingAction, $booking)) {
                continue;
            }
            $this->warn("Unresolved dispute #{$booking->id}");
            if (! $isDryRun) {
                $this->alerts->create(
                    type: AlertType::PendingAction,
                    severity: AlertSeverity::Critical,
                    title: 'Litige non résolu',
                    description: "La réservation #{$booking->id} est en litige depuis plus de {$reminderAt}h.",
                    subject: $booking,
                );
            }
        }

        // Reported reviews older than threshold
        $reportedReviews = Review::where('is_reported', true)
            ->where('reported_at', '<', $threshold)
            ->get();

        foreach ($reportedReviews as $review) {
            if ($this->alerts->openExists(AlertType::PendingAction, $review)) {
                continue;
            }
            $this->warn("Pending reported review #{$review->id}");
            if (! $isDryRun) {
                $this->alerts->create(
                    type: AlertType::PendingAction,
                    severity: AlertSeverity::Warning,
                    title: 'Avis signalé en attente',
                    description: "L'avis #{$review->id} est signalé depuis plus de {$reminderAt}h sans décision.",
                    subject: $review,
                );
            }
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
