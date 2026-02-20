<?php

namespace App\Providers;

use App\Contracts\PaymentGatewayInterface;
use App\Events\BookingAccepted;
use App\Events\BookingCancelled;
use App\Events\BookingCreated;
use App\Events\EscrowReleased;
use App\Events\PaymentReceived;
use App\Gateways\FedaPayGateway;
use App\Gateways\PaymentGatewayResolver;
use App\Gateways\PaystackGateway;
use App\Listeners\HandleEscrowReleased;
use App\Listeners\NotifyClientOfBookingAccepted;
use App\Listeners\NotifyPartyOfBookingCancelled;
use App\Listeners\NotifyTalentOfNewBooking;
use App\Listeners\NotifyTalentOfPaymentReceived;
use App\Models\TalentProfile;
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
use Illuminate\Support\Facades\Event;
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
        TalentProfile::observe(TalentProfileObserver::class);
        $this->configureRateLimiting();
        Event::listen(EscrowReleased::class,   HandleEscrowReleased::class);
        Event::listen(BookingCreated::class,   NotifyTalentOfNewBooking::class);
        Event::listen(BookingAccepted::class,  NotifyClientOfBookingAccepted::class);
        Event::listen(BookingCancelled::class, NotifyPartyOfBookingCancelled::class);
        Event::listen(PaymentReceived::class,  NotifyTalentOfPaymentReceived::class);
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
