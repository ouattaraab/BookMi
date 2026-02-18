<?php

namespace App\Providers;

use App\Repositories\Contracts\FavoriteRepositoryInterface;
use App\Repositories\Contracts\ServicePackageRepositoryInterface;
use App\Repositories\Contracts\TalentRepositoryInterface;
use App\Repositories\Contracts\VerificationRepositoryInterface;
use App\Repositories\Eloquent\FavoriteRepository;
use App\Repositories\Eloquent\ServicePackageRepository;
use App\Repositories\Eloquent\TalentRepository;
use App\Repositories\Eloquent\VerificationRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TalentRepositoryInterface::class, TalentRepository::class);
        $this->app->bind(VerificationRepositoryInterface::class, VerificationRepository::class);
        $this->app->bind(ServicePackageRepositoryInterface::class, ServicePackageRepository::class);
        $this->app->bind(FavoriteRepositoryInterface::class, FavoriteRepository::class);
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $limit = $request->user()
                ? config('bookmi.rate_limits.authenticated', 60)
                : config('bookmi.rate_limits.unauthenticated', 30);

            return Limit::perMinute($limit)->by(
                $request->user()?->id ?: $request->ip()
            );
        });

        RateLimiter::for('payment', function (Request $request) {
            return Limit::perMinute(
                config('bookmi.rate_limits.payment', 10)
            )->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(
                config('bookmi.rate_limits.auth_endpoints', 10)
            )->by($request->ip());
        });

        RateLimiter::for('forgot-password', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}
