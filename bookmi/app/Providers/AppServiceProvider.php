<?php

namespace App\Providers;

use App\Contracts\PaymentGatewayInterface;
use App\Gateways\FedaPayGateway;
use App\Gateways\PaymentGatewayResolver;
use App\Gateways\PaystackGateway;
use App\Models\BookingRequest;
use App\Models\PortfolioItem;
use App\Models\TalentProfile;
use App\Observers\BookingRequestObserver;
use App\Observers\PortfolioItemObserver;
use App\Observers\TalentProfileObserver;
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
use Illuminate\Support\Facades\Gate;
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
        $this->app->bind(PaymentGatewayInterface::class, function ($app) {
            return new PaymentGatewayResolver(
                $app->make(PaystackGateway::class),
                $app->make(FedaPayGateway::class),
            );
        });
    }

    public function boot(): void
    {
        // ── Super-admin bypass : les utilisateurs is_admin ont accès complet
        // au panel Filament (canViewAny, canAccess, canCreate, canEdit, canDelete…).
        // Ce Gate::before n'affecte pas les utilisateurs API normaux (qui n'ont pas is_admin).
        Gate::before(function ($user, string $ability) {
            // Les attributs Eloquent ne sont pas des propriétés PHP réelles :
            // property_exists() échoue → utiliser isset() qui passe par __isset().
            if (isset($user->is_admin) && $user->is_admin === true) {
                return true;
            }

            return null; // laisse les vérifications normales s'appliquer
        });

        TalentProfile::observe(TalentProfileObserver::class);
        BookingRequest::observe(BookingRequestObserver::class);
        PortfolioItem::observe(PortfolioItemObserver::class);
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

        RateLimiter::for('booking', function (Request $request) {
            // 10 créations de réservation par minute par utilisateur
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('messaging', function (Request $request) {
            // 60 messages par minute par utilisateur
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
