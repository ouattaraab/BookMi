<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

class FailedJobsPage extends Page
{
    protected static ?string $slug            = 'failed-jobs';
    protected static ?string $navigationIcon  = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationLabel = 'Jobs échoués';
    protected static ?string $title           = 'Jobs échoués';
    protected static ?string $navigationGroup = 'Paramètres';
    protected static ?int    $navigationSort  = 15;
    protected static string  $view            = 'filament.pages.failed-jobs-page';

    /** @var array<int, array<string, mixed>> */
    public array $jobs = [];

    /**
     * Per-job retry status: 'running' | 'success' | 'failed'
     *
     * @var array<string, string>
     */
    public array $retryStatuses = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return ($user?->is_admin === true) || ($user?->hasRole('admin_ceo') ?? false);
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            $count = DB::table('failed_jobs')->count();

            return $count > 0 ? (string) $count : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public function mount(): void
    {
        $this->loadJobs();
    }

    public function loadJobs(): void
    {
        $this->jobs = DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(100)
            ->get()
            ->map(fn ($job) => [
                'id'        => $job->uuid ?? (string) $job->id,
                'queue'     => $job->queue,
                'job_class' => $this->extractJobClass((string) ($job->payload ?? '')),
                'exception' => mb_substr($job->exception ?? '', 0, 300),
                'failed_at' => $job->failed_at,
            ])
            ->toArray();
    }

    private function extractJobClass(string $payload): string
    {
        try {
            $data = json_decode($payload, true);

            return is_array($data)
                ? ($data['displayName'] ?? $data['data']['commandName'] ?? 'Inconnu')
                : 'Inconnu';
        } catch (\Throwable) {
            return 'Inconnu';
        }
    }

    /**
     * Deserialize the job payload and execute it synchronously via the Bus.
     * Updates retryStatuses[$id] to 'success' or 'failed' with immediate feedback.
     */
    public function retry(string $id): void
    {
        $job = DB::table('failed_jobs')
            ->where('uuid', $id)
            ->orWhere('id', $id)
            ->first();

        if ($job === null) {
            Notification::make()->title('Job introuvable')->warning()->send();

            return;
        }

        $this->retryStatuses[$id] = 'running';

        try {
            $command = $this->deserializeCommand((string) ($job->payload ?? ''));

            Bus::dispatchSync($command);

            // Success: remove from failed_jobs
            DB::table('failed_jobs')->where('uuid', $id)->delete();
            $this->retryStatuses[$id] = 'success';

            Notification::make()
                ->title('Job exécuté avec succès')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            // Keep in failed_jobs, update exception with latest error
            DB::table('failed_jobs')
                ->where('uuid', $id)
                ->update([
                    'exception' => mb_substr(
                        $e->getMessage() . "\n" . $e->getTraceAsString(),
                        0,
                        65535
                    ),
                    'failed_at' => now()->toDateTimeString(),
                ]);

            $this->retryStatuses[$id] = 'failed';

            Notification::make()
                ->title('Échec de la réexécution')
                ->body(mb_substr($e->getMessage(), 0, 150))
                ->danger()
                ->send();
        }

        $this->loadJobs();
    }

    /**
     * Re-execute all failed jobs synchronously (capped at 10 to avoid timeout).
     * Returns aggregate success/failure counts.
     */
    public function retryAll(): void
    {
        $failedJobs = DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(10)
            ->get();

        if ($failedJobs->isEmpty()) {
            Notification::make()->title('Aucun job à relancer')->warning()->send();

            return;
        }

        $succeeded = 0;
        $failed    = 0;

        foreach ($failedJobs as $job) {
            $id = $job->uuid ?? (string) $job->id;

            try {
                $command = $this->deserializeCommand((string) ($job->payload ?? ''));

                Bus::dispatchSync($command);

                DB::table('failed_jobs')->where('uuid', $id)->delete();
                $this->retryStatuses[$id] = 'success';
                $succeeded++;
            } catch (\Throwable $e) {
                DB::table('failed_jobs')
                    ->where('uuid', $id)
                    ->update([
                        'exception' => mb_substr(
                            $e->getMessage() . "\n" . $e->getTraceAsString(),
                            0,
                            65535
                        ),
                        'failed_at' => now()->toDateTimeString(),
                    ]);

                $this->retryStatuses[$id] = 'failed';
                $failed++;
            }
        }

        Notification::make()
            ->title("{$succeeded} succès · {$failed} échec(s)")
            ->color($failed > 0 ? 'warning' : 'success')
            ->send();

        $this->loadJobs();
    }

    public function flushAll(): void
    {
        try {
            Artisan::call('queue:flush');
            Notification::make()->title('Tous les jobs échoués supprimés')->success()->send();
            $this->jobs          = [];
            $this->retryStatuses = [];
        } catch (\Throwable $e) {
            Notification::make()->title('Erreur : ' . $e->getMessage())->danger()->send();
        }
    }

    public function getTitle(): string|Htmlable
    {
        return 'Jobs échoués';
    }

    /**
     * Deserialize a Laravel queued job command from its JSON payload.
     *
     * @throws \RuntimeException
     */
    private function deserializeCommand(string $rawPayload): object
    {
        $payload = json_decode($rawPayload, true);

        $serialized = is_array($payload) && isset($payload['data']['command'])
            ? $payload['data']['command']
            : null;

        if (! is_string($serialized)) {
            throw new \RuntimeException(
                'Payload du job non désérialisable (format inattendu).'
            );
        }

        $command = unserialize($serialized);

        if (! is_object($command)) {
            throw new \RuntimeException(
                'La commande désérialisée n\'est pas un objet valide.'
            );
        }

        return $command;
    }
}
