<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookingRequestController;
use App\Http\Controllers\Api\V1\CalendarSlotController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\HealthCheckController;
use App\Http\Controllers\Api\V1\TalentController;
use App\Http\Controllers\Api\V1\ServicePackageController;
use App\Http\Controllers\Api\V1\TalentProfileController;
use App\Http\Controllers\Api\V1\VerificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('/health', HealthCheckController::class)->name('health');

    Route::post('/auth/register', [AuthController::class, 'register'])
        ->middleware('throttle:auth')
        ->name('auth.register');

    Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp'])
        ->middleware('throttle:auth')
        ->name('auth.verify-otp');

    Route::post('/auth/resend-otp', [AuthController::class, 'resendOtp'])
        ->middleware('throttle:auth')
        ->name('auth.resend-otp');

    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:auth')
        ->name('auth.login');

    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:forgot-password')
        ->name('auth.forgot-password');

    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:auth')
        ->name('auth.reset-password');

    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');

    Route::get('/talents', [TalentController::class, 'index'])->name('talents.index');
    Route::get('/talents/{slug}', [TalentController::class, 'show'])->name('talents.show');
    Route::get('/talents/{talent}/calendar', [CalendarSlotController::class, 'index'])->name('calendar.index');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout'])
            ->middleware('throttle:auth')
            ->name('auth.logout');

        Route::get('/me', [AuthController::class, 'me'])
            ->middleware('throttle:auth')
            ->name('me');

        Route::post('/talent_profiles', [TalentProfileController::class, 'store'])->name('talent_profiles.store');
        Route::get('/talent_profiles/me', [TalentProfileController::class, 'showOwn'])->name('talent_profiles.me');
        Route::patch('/talent_profiles/{talent_profile}', [TalentProfileController::class, 'update'])->name('talent_profiles.update');
        Route::delete('/talent_profiles/{talent_profile}', [TalentProfileController::class, 'destroy'])->name('talent_profiles.destroy');

        Route::post('/verifications', [VerificationController::class, 'store'])->name('verifications.store');
        Route::get('/verifications/me', [VerificationController::class, 'showOwn'])->name('verifications.me');

        Route::apiResource('service_packages', ServicePackageController::class)->except(['show']);

        // Calendrier disponibilités (talent uniquement)
        Route::post('/calendar_slots', [CalendarSlotController::class, 'store'])->name('calendar.store');
        Route::put('/calendar_slots/{slot}', [CalendarSlotController::class, 'update'])->name('calendar.update');
        Route::delete('/calendar_slots/{slot}', [CalendarSlotController::class, 'destroy'])->name('calendar.destroy');

        // Réservations
        Route::get('/booking_requests', [BookingRequestController::class, 'index'])->name('booking_requests.index');
        Route::post('/booking_requests', [BookingRequestController::class, 'store'])->name('booking_requests.store');
        Route::get('/booking_requests/{booking}', [BookingRequestController::class, 'show'])->name('booking_requests.show');
        Route::post('/booking_requests/{booking}/accept', [BookingRequestController::class, 'accept'])->name('booking_requests.accept');
        Route::post('/booking_requests/{booking}/reject', [BookingRequestController::class, 'reject'])->name('booking_requests.reject');
        Route::post('/booking_requests/{booking}/cancel', [BookingRequestController::class, 'cancel'])->name('booking_requests.cancel');
        Route::get('/booking_requests/{booking}/contract', [BookingRequestController::class, 'contract'])->name('booking_requests.contract');

        // Favoris
        Route::get('/me/favorites', [FavoriteController::class, 'index'])
            ->name('favorites.index');
        Route::post('/talents/{talentProfileId}/favorite', [FavoriteController::class, 'store'])
            ->name('favorites.store');
        Route::delete('/talents/{talentProfileId}/favorite', [FavoriteController::class, 'destroy'])
            ->name('favorites.destroy');
        Route::get('/talents/{talentProfileId}/favorite', [FavoriteController::class, 'check'])
            ->name('favorites.check');
    });
});
