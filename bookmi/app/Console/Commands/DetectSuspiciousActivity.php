<?php

namespace App\Console\Commands;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use App\Models\User;
use App\Services\AlertService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DetectSuspiciousActivity extends Command
{
    protected $signature   = 'bookmi:detect-suspicious-activity {--dry-run : Log without creating alerts}';
    protected $description = 'Detect suspicious patterns: duplicate identities, multiple registrations (Story 8.5)';

    public function __construct(private readonly AlertService $alerts)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $this->info('Detecting suspicious activity…');

        $this->detectDuplicatePhones($isDryRun);
        $this->detectMultipleRegistrationsSameDay($isDryRun);

        $this->info('Done.');

        return self::SUCCESS;
    }

    /**
     * Detect multiple accounts sharing the same phone prefix (first 10 digits).
     */
    private function detectDuplicatePhones(bool $isDryRun): void
    {
        $duplicates = DB::table('users')
            ->select(DB::raw('SUBSTR(phone, 1, 10) as phone_prefix'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('phone_prefix')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($duplicates as $dup) {
            $users = User::where('phone', 'like', $dup->phone_prefix . '%')->get();
            $ids   = $users->pluck('id')->join(', ');
            $this->warn("Duplicate phone prefix {$dup->phone_prefix}: users [{$ids}]");

            if (! $isDryRun) {
                $this->alerts->create(
                    type: AlertType::SuspiciousActivity,
                    severity: AlertSeverity::Critical,
                    title: 'Identité dupliquée détectée',
                    description: "Plusieurs comptes partagent le préfixe téléphonique {$dup->phone_prefix} (users: {$ids}).",
                    metadata: ['phone_prefix' => $dup->phone_prefix, 'user_ids' => $users->pluck('id')->toArray()],
                );
            }
        }
    }

    /**
     * Detect multiple registrations in the same day (> 3 in one hour).
     */
    private function detectMultipleRegistrationsSameDay(bool $isDryRun): void
    {
        $since  = Carbon::now()->subHour();
        $recent = User::where('created_at', '>=', $since)->count();

        if ($recent > 3) {
            $this->warn("High registration volume: {$recent} accounts in the last hour.");

            if (! $isDryRun) {
                $this->alerts->create(
                    type: AlertType::SuspiciousActivity,
                    severity: AlertSeverity::Warning,
                    title: 'Volume d\'inscriptions anormal',
                    description: "{$recent} comptes créés dans la dernière heure (seuil: 3).",
                    metadata: ['count' => $recent, 'since' => $since->toIso8601String()],
                );
            }
        }
    }
}
