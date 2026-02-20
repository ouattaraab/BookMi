<?php

use App\Http\Controllers\Web\Auth\WebForgotPasswordController;
use App\Http\Controllers\Web\Auth\WebLoginController;
use App\Http\Controllers\Web\Auth\WebPhoneVerifyController;
use App\Http\Controllers\Web\Auth\WebRegisterController;
use App\Http\Controllers\Web\Auth\WebResetPasswordController;
use App\Http\Controllers\Web\Auth\WebTwoFactorController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\TalentDiscoveryController;
use App\Http\Controllers\Web\TalentNotificationController;
use App\Http\Controllers\Web\TalentPageController;
use Illuminate\Support\Facades\Route;

// ── Public ─────────────────────────────────────────────────────────────────
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/talents', [TalentDiscoveryController::class, 'index'])->name('talents.index');
Route::post('/talents/notify', [TalentNotificationController::class, 'store'])->name('talents.notify');
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

// ── Admin JSON API ──────────────────────────────────────────────────────────
// Routes at /admin/* returning JSON — used by admin backoffice and tests.
// Uses auth:web guard + EnsureUserIsAdmin (is_admin check).
// NOTE: registered before Filament panel routes to ensure priority.
Route::prefix('admin')->name('admin.web.')->middleware(['auth:web', 'admin'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [\App\Http\Controllers\Api\V1\AdminDashboardController::class, 'index'])->name('dashboard');

    // Alerts
    Route::get('/alerts', [\App\Http\Controllers\Api\V1\AdminAlertController::class, 'index'])->name('alerts.index');
    Route::post('/alerts/{alert}/resolve', [\App\Http\Controllers\Api\V1\AdminAlertController::class, 'resolve'])->name('alerts.resolve');
    Route::post('/alerts/{alert}/dismiss', [\App\Http\Controllers\Api\V1\AdminAlertController::class, 'dismiss'])->name('alerts.dismiss');

    // Audit trail
    Route::get('/audit', [\App\Http\Controllers\Api\V1\AdminAuditController::class, 'index'])->name('audit.index');

    // Disputes
    Route::get('/disputes', [\App\Http\Controllers\Api\V1\AdminDisputeController::class, 'index'])->name('disputes.index');
    Route::get('/disputes/{booking}', [\App\Http\Controllers\Api\V1\AdminDisputeController::class, 'show'])->name('disputes.show');
    Route::post('/disputes/{booking}/notes', [\App\Http\Controllers\Api\V1\AdminDisputeController::class, 'addNote'])->name('disputes.notes');
    Route::post('/disputes/{booking}/resolve', [\App\Http\Controllers\Api\V1\AdminDisputeController::class, 'resolve'])->name('disputes.resolve');

    // Review moderation
    Route::get('/reviews/reported', [\App\Http\Controllers\Api\V1\AdminReviewModerationController::class, 'reported'])->name('reviews.reported');
    Route::post('/reviews/{review}/approve', [\App\Http\Controllers\Api\V1\AdminReviewModerationController::class, 'approve'])->name('reviews.approve');
    Route::delete('/reviews/{review}', [\App\Http\Controllers\Api\V1\AdminReviewModerationController::class, 'destroy'])->name('reviews.destroy');
    Route::patch('/reviews/{review}', [\App\Http\Controllers\Api\V1\AdminReviewModerationController::class, 'update'])->name('reviews.update');

    // Team management (Story 8.6)
    Route::get('/team', [\App\Http\Controllers\Api\V1\AdminUserController::class, 'team'])->name('team.index');
    Route::post('/team', [\App\Http\Controllers\Api\V1\AdminUserController::class, 'createCollaborator'])->name('team.store');
    Route::put('/team/{user}', [\App\Http\Controllers\Api\V1\AdminUserController::class, 'updateCollaboratorRole'])->name('team.update');
    Route::delete('/team/{user}', [\App\Http\Controllers\Api\V1\AdminUserController::class, 'revokeCollaborator'])->name('team.destroy');

    // User management — warnings, suspend, unsuspend (Stories 8.2 & 8.3)
    Route::get('/users', [\App\Http\Controllers\Api\V1\AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [\App\Http\Controllers\Api\V1\AdminUserController::class, 'show'])->name('users.show');
    Route::post('/users/{user}/warnings', [\App\Http\Controllers\Api\V1\AdminUserController::class, 'createWarning'])->name('users.warnings');
    Route::post('/users/{user}/suspend', [\App\Http\Controllers\Api\V1\AdminUserController::class, 'suspend'])->name('users.suspend');
    Route::post('/users/{user}/unsuspend', [\App\Http\Controllers\Api\V1\AdminUserController::class, 'unsuspend'])->name('users.unsuspend');

    // Identity verifications (Stories 8.1)
    // NOTE: /document must come before /{verification} to avoid shadowing
    Route::get('/verifications', [\App\Http\Controllers\Api\V1\AdminVerificationController::class, 'index'])->name('verifications.index');
    Route::get('/verifications/{verification}/document', [\App\Http\Controllers\Api\V1\AdminVerificationController::class, 'document'])->name('verifications.document');
    Route::get('/verifications/{verification}', [\App\Http\Controllers\Api\V1\AdminVerificationController::class, 'show'])->name('verifications.show');
    Route::post('/verifications/{verification}/review', [\App\Http\Controllers\Api\V1\AdminVerificationController::class, 'review'])->name('verifications.review');
});
