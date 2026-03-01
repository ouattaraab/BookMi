<?php

use App\Http\Controllers\Web\Talent\BookingController;
use App\Http\Controllers\Web\Talent\CalendarController;
use App\Http\Controllers\Web\Talent\ReviewController;
use App\Http\Controllers\Web\Talent\CertificateController;
use App\Http\Controllers\Web\Talent\DashboardController;
use App\Http\Controllers\Web\Talent\EarningsController;
use App\Http\Controllers\Web\Talent\MessageController;
use App\Http\Controllers\Web\Talent\PackageController;
use App\Http\Controllers\Web\Talent\PaiementController;
use App\Http\Controllers\Web\Talent\PortfolioController;
use App\Http\Controllers\Web\Talent\ProfileController;
use App\Http\Controllers\Web\Talent\RevenueCertificateController;
use App\Http\Controllers\Web\Talent\SettingsController;
use App\Http\Controllers\Web\Talent\StatisticsController;
use App\Http\Controllers\Web\Talent\VerificationController;
use Illuminate\Support\Facades\Route;

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Calendrier
Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');
Route::post('/calendar/availability', [CalendarController::class, 'setAvailability'])->name('calendar.availability');
Route::delete('/calendar/availability/{id}', [CalendarController::class, 'removeAvailability'])->name('calendar.availability.destroy');

// Réservations
Route::get('/bookings', [BookingController::class, 'index'])->name('bookings');
Route::get('/bookings/{id}', [BookingController::class, 'show'])->name('bookings.show');
Route::post('/bookings/{id}/accept', [BookingController::class, 'accept'])->name('bookings.accept');
Route::post('/bookings/{id}/reject', [BookingController::class, 'reject'])->name('bookings.reject');
Route::post('/bookings/{id}/complete', [BookingController::class, 'complete'])->name('bookings.complete');
Route::post('/bookings/{id}/talent-confirm', [BookingController::class, 'talentConfirm'])->name('bookings.talent_confirm');
Route::post('/bookings/{id}/checkin', [BookingController::class, 'checkin'])->name('bookings.checkin');
Route::post('/bookings/{id}/reviews/{reviewId}/reply', [ReviewController::class, 'reply'])->name('bookings.review.reply');
Route::post('/bookings/{id}/review', [ReviewController::class, 'reviewClient'])->name('bookings.review.client');

// Packages / Offres
Route::get('/packages', [PackageController::class, 'index'])->name('packages');
Route::post('/packages', [PackageController::class, 'store'])->name('packages.store');
Route::put('/packages/{id}', [PackageController::class, 'update'])->name('packages.update');
Route::delete('/packages/{id}', [PackageController::class, 'destroy'])->name('packages.destroy');

// Messages
Route::get('/messages', [MessageController::class, 'index'])->name('messages');
Route::get('/messages/{id}', [MessageController::class, 'show'])->name('messages.show');
Route::post('/messages/{id}', [MessageController::class, 'send'])->name('messages.send');

// Statistiques
Route::get('/statistics', [StatisticsController::class, 'index'])->name('statistics');

// Mes Revenus
Route::get('/earnings', [EarningsController::class, 'index'])->name('earnings');

// Moyens de paiement
Route::get('/paiement', [PaiementController::class, 'index'])->name('paiement');
Route::post('/paiement/account', [PaiementController::class, 'updatePayoutMethod'])->name('paiement.account.update');
Route::delete('/paiement/account', [PaiementController::class, 'deletePayoutMethod'])->name('paiement.account.delete');
Route::post('/paiement/withdrawal', [PaiementController::class, 'storeWithdrawal'])->name('paiement.withdrawal.store');

// Attestation (PDF)
Route::get('/certificate/{bookingId}', [CertificateController::class, 'download'])->name('certificate');

// Attestation de revenus annuelle
Route::get('/revenue-certificate', [RevenueCertificateController::class, 'index'])->name('revenue-certificate');
Route::get('/revenue-certificate/download', [RevenueCertificateController::class, 'download'])->name('revenue-certificate.download');

// Portfolio
Route::get('/portfolio', [PortfolioController::class, 'index'])->name('portfolio');
Route::post('/portfolio/upload', [PortfolioController::class, 'upload'])->name('portfolio.upload');
Route::post('/portfolio/link', [PortfolioController::class, 'addLink'])->name('portfolio.link');
Route::delete('/portfolio/{id}', [PortfolioController::class, 'destroy'])->name('portfolio.destroy');

// Profil
Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo');

// Vérification
Route::get('/verification', [VerificationController::class, 'index'])->name('verification');
Route::post('/verification', [VerificationController::class, 'submit'])->name('verification.submit');

// Paramètres (2FA)
Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
Route::post('/settings/2fa/setup/totp', [SettingsController::class, 'setupTotp'])->name('settings.2fa.setup.totp');
Route::post('/settings/2fa/enable/totp', [SettingsController::class, 'enableTotp'])->name('settings.2fa.enable.totp');
Route::post('/settings/2fa/setup/email', [SettingsController::class, 'setupEmail'])->name('settings.2fa.setup.email');
Route::post('/settings/2fa/enable/email', [SettingsController::class, 'enableEmail'])->name('settings.2fa.enable.email');
Route::post('/settings/2fa/disable', [SettingsController::class, 'disable'])->name('settings.2fa.disable');
Route::post('/settings/notifications', [SettingsController::class, 'updateNotificationPreferences'])->name('settings.notifications.update');
