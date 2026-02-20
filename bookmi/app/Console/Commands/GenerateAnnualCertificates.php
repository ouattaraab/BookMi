<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\BookingRequest;
use App\Models\TalentProfile;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Generates annual revenue certificates (PDF) for all talents
 * who had at least one completed booking in the target year.
 *
 * Scheduled: every January 1st at 06:00 (generates certificates for the previous year).
 * Can also be run manually: php artisan bookmi:generate-annual-certificates --year=2025
 */
class GenerateAnnualCertificates extends Command
{
    protected $signature = 'bookmi:generate-annual-certificates
                            {--year= : Year to generate certificates for (defaults to previous year)}
                            {--talent= : Restrict generation to a specific talent_profile_id}';

    protected $description = 'Generate annual revenue certificates (PDF) for all active talents';

    public function handle(): int
    {
        $year = (int) ($this->option('year') ?? now()->subYear()->year);

        if ($year < 2020 || $year > now()->year) {
            $this->error("Invalid year: {$year}. Must be between 2020 and " . now()->year . '.');

            return self::FAILURE;
        }

        $this->info("Generating annual revenue certificates for year {$year}...");

        $query = BookingRequest::where('status', BookingStatus::Completed->value)
            ->whereYear('event_date', $year)
            ->distinct()
            ->select('talent_profile_id');

        if ($this->option('talent')) {
            $query->where('talent_profile_id', (int) $this->option('talent'));
        }

        $talentIds = $query->pluck('talent_profile_id');

        $talents = TalentProfile::whereIn('id', $talentIds)->with('user')->get();

        $this->info("Found {$talents->count()} talent(s) with completed bookings in {$year}.");

        if ($talents->isEmpty()) {
            $this->warn('No certificates to generate.');

            return self::SUCCESS;
        }

        $bar       = $this->output->createProgressBar($talents->count());
        $generated = 0;
        $errors    = 0;

        $bar->start();

        foreach ($talents as $talent) {
            try {
                $path = $this->generateCertificate($talent, $year);
                $generated++;
                $this->newLine();
                $this->line("  ✓ {$talent->stage_name} → storage/{$path}");
            } catch (\Throwable $e) {
                $errors++;
                $this->newLine();
                $this->error("  ✗ {$talent->stage_name}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done. Generated: {$generated} | Errors: {$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Generate the PDF for one talent and persist it to storage.
     *
     * @return string The relative path within storage (e.g. "certificates/2025/attestation-revenus-2025-42.pdf")
     */
    private function generateCertificate(TalentProfile $talent, int $year): string
    {
        $user = $talent->user;

        $yearlyBookings = BookingRequest::where('talent_profile_id', $talent->id)
            ->where('status', BookingStatus::Completed->value)
            ->whereYear('event_date', $year)
            ->get(['event_date', 'cachet_amount', 'commission_amount', 'total_amount']);

        $monthlyBreakdown = $yearlyBookings
            ->groupBy(fn ($b) => (int) \Carbon\Carbon::parse($b->event_date)->format('m'))
            ->map(fn ($group, $month) => [
                'month'             => $month,
                'bookings_count'    => $group->count(),
                'gross_amount_xof'  => (int) $group->sum('cachet_amount'),
                'commission_xof'    => (int) $group->sum('commission_amount'),
                'net_amount_xof'    => (int) $group->sum('total_amount'),
            ])
            ->sortKeys()
            ->values()
            ->toArray();

        $data = [
            'talent' => [
                'stage_name' => $talent->stage_name,
                'full_name'  => $user->first_name . ' ' . $user->last_name,
                'email'      => $user->email,
                'phone'      => $user->phone,
            ],
            'year'         => $year,
            'generated_at' => now()->format('d/m/Y'),
            'monthly_breakdown' => $monthlyBreakdown,
            'totals' => [
                'bookings_count'   => $yearlyBookings->count(),
                'gross_amount_xof' => (int) $yearlyBookings->sum('cachet_amount'),
                'commission_xof'   => (int) $yearlyBookings->sum('commission_amount'),
                'net_amount_xof'   => (int) $yearlyBookings->sum('total_amount'),
            ],
        ];

        $pdf = Pdf::loadView('pdf.revenue_certificate', $data)->setPaper('a4', 'portrait');

        $directory = "certificates/{$year}";
        $filename  = "attestation-revenus-{$year}-{$talent->id}.pdf";
        $path      = "{$directory}/{$filename}";

        Storage::put($path, $pdf->output());

        return $path;
    }
}
