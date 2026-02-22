<?php

use App\Http\Controllers\Web\Client\BookingController;
use App\Http\Controllers\Web\Client\DashboardController;
use App\Http\Controllers\Web\Client\FavoriteController;
use App\Http\Controllers\Web\Client\MessageController;
use App\Http\Controllers\Web\Client\SettingsController;
use Illuminate\Support\Facades\Route;

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Réservations
Route::get('/bookings', [BookingController::class, 'index'])->name('bookings');
Route::get('/bookings/create', [BookingController::class, 'create'])->name('bookings.create');
Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
Route::get('/bookings/{id}', [BookingController::class, 'show'])->name('bookings.show');
Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
Route::get('/bookings/{id}/pay', [BookingController::class, 'pay'])->name('bookings.pay');
Route::post('/bookings/{id}/pay', [BookingController::class, 'processPayment'])->name('bookings.pay.process');
Route::post('/bookings/{id}/pay/otp', [BookingController::class, 'submitOtp'])->name('bookings.pay.otp');
Route::get('/bookings/payment/callback', [BookingController::class, 'paymentCallback'])->name('bookings.payment.callback');

// Favoris
Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites');
Route::delete('/favorites/{id}', [FavoriteController::class, 'destroy'])->name('favorites.destroy');
Route::post('/favorites/{talentProfileId}', [FavoriteController::class, 'store'])->name('favorites.store');

// Messages
Route::get('/messages', [MessageController::class, 'index'])->name('messages');
Route::post('/messages/booking/{bookingId}', [MessageController::class, 'startFromBooking'])->name('messages.start');
Route::get('/messages/{id}', [MessageController::class, 'show'])->name('messages.show');
Route::post('/messages/{id}', [MessageController::class, 'send'])->name('messages.send');

// Paramètres (2FA)
Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
Route::post('/settings/2fa/setup/totp', [SettingsController::class, 'setupTotp'])->name('settings.2fa.setup.totp');
Route::post('/settings/2fa/enable/totp', [SettingsController::class, 'enableTotp'])->name('settings.2fa.enable.totp');
Route::post('/settings/2fa/setup/email', [SettingsController::class, 'setupEmail'])->name('settings.2fa.setup.email');
Route::post('/settings/2fa/enable/email', [SettingsController::class, 'enableEmail'])->name('settings.2fa.enable.email');
Route::post('/settings/2fa/disable', [SettingsController::class, 'disable'])->name('settings.2fa.disable');
