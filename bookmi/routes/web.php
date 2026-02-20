<?php

use App\Http\Controllers\Web\Auth\WebForgotPasswordController;
use App\Http\Controllers\Web\Auth\WebLoginController;
use App\Http\Controllers\Web\Auth\WebPhoneVerifyController;
use App\Http\Controllers\Web\Auth\WebRegisterController;
use App\Http\Controllers\Web\Auth\WebResetPasswordController;
use App\Http\Controllers\Web\Auth\WebTwoFactorController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\TalentDiscoveryController;
use App\Http\Controllers\Web\TalentPageController;
use Illuminate\Support\Facades\Route;

// ── Public ─────────────────────────────────────────────────────────────────
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/talents', [TalentDiscoveryController::class, 'index'])->name('talents.index');
Route::get('/talents/{slug}', [TalentPageController::class, 'show'])->name('talent.show');

// ── Auth (guest only) ───────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [WebLoginController::class, 'showForm'])->name('login');
    Route::post('/login', [WebLoginController::class, 'login']);

    Route::get('/register', [WebRegisterController::class, 'showForm'])->name('register');
    Route::post('/register', [WebRegisterController::class, 'register']);

    Route::get('/forgot-password', [WebForgotPasswordController::class, 'showForm'])->name('password.request');
    Route::post('/forgot-password', [WebForgotPasswordController::class, 'send'])->name('password.email');

    Route::get('/reset-password/{token}', [WebResetPasswordController::class, 'showForm'])->name('password.reset');
    Route::post('/reset-password', [WebResetPasswordController::class, 'reset'])->name('password.update');
});

// ── 2FA challenge (no full auth needed, only challenge token in session) ────
Route::get('/auth/2fa/challenge', [WebTwoFactorController::class, 'showChallenge'])->name('auth.2fa.challenge');
Route::post('/auth/2fa/challenge', [WebTwoFactorController::class, 'verify'])->name('auth.2fa.verify');

// ── Phone OTP verify (no full auth yet) ────────────────────────────────────
Route::get('/auth/verify-phone', [WebPhoneVerifyController::class, 'showOtp'])->name('auth.verify-phone');
Route::post('/auth/verify-phone', [WebPhoneVerifyController::class, 'verify'])->name('auth.verify-phone.submit');
Route::post('/auth/verify-phone/resend', [WebPhoneVerifyController::class, 'resend'])->name('auth.verify-phone.resend');

// ── Logout ──────────────────────────────────────────────────────────────────
Route::post('/logout', [WebLoginController::class, 'logout'])->middleware('auth')->name('logout');

// ── Espace Client ───────────────────────────────────────────────────────────
Route::prefix('client')->name('client.')->middleware(['auth:web', 'role:client'])->group(
    base_path('routes/client.php')
);

// ── Espace Talent ───────────────────────────────────────────────────────────
Route::prefix('talent')->name('talent.')->middleware(['auth:web', 'role:talent'])->group(
    base_path('routes/talent.php')
);

// ── Espace Manager ──────────────────────────────────────────────────────────
Route::prefix('manager')->name('manager.')->middleware(['auth:web', 'role:manager'])->group(
    base_path('routes/manager.php')
);
