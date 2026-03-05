<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Artisan;
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
                'exception' => mb_substr($job->exception ?? '', 0, 300),
                'failed_at' => $job->failed_at,
            ])
            ->toArray();
    }

    public function retry(string $id): void
    {
        try {
            Artisan::call('queue:retry', ['id' => [$id]]);
            Notification::make()->title('Job re-planifié')->success()->send();
            $this->loadJobs();
        } catch (\Throwable $e) {
            Notification::make()->title('Erreur : ' . $e->getMessage())->danger()->send();
        }
    }

    public function flushAll(): void
    {
        try {
            Artisan::call('queue:flush');
            Notification::make()->title('Tous les jobs échoués supprimés')->success()->send();
            $this->jobs = [];
        } catch (\Throwable $e) {
            Notification::make()->title('Erreur : ' . $e->getMessage())->danger()->send();
        }
    }

    public function getTitle(): string|Htmlable
    {
        return 'Jobs échoués';
    }
}
