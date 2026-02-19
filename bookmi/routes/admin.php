<?php

use App\Http\Controllers\Admin\VerificationController;
use App\Http\Controllers\Api\V1\AdminAlertController;
use App\Http\Controllers\Api\V1\AdminAuditController;
use App\Http\Controllers\Api\V1\AdminDashboardController;
use App\Http\Controllers\Api\V1\AdminDisputeController;
use App\Http\Controllers\Api\V1\AdminKpiController;
use App\Http\Controllers\Api\V1\AdminOperationsController;
use App\Http\Controllers\Api\V1\AdminReviewModerationController;
use App\Http\Controllers\Api\V1\AdminUserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->group(function () {
    // Identity verifications (Epic 1.4)
    Route::get('/verifications', [VerificationController::class, 'index'])->name('verifications.index');
    Route::get('/verifications/{verification}', [VerificationController::class, 'show'])->name('verifications.show');
    Route::get('/verifications/{verification}/document', [VerificationController::class, 'document'])->name('verifications.document');
    Route::post('/verifications/{verification}/review', [VerificationController::class, 'review'])->name('verifications.review');

    // 8.1 — Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // 8.2 — Disputes
    Route::get('/disputes', [AdminDisputeController::class, 'index'])->name('disputes.index');
    Route::get('/disputes/{booking}', [AdminDisputeController::class, 'show'])->name('disputes.show');
    Route::get('/disputes/{booking}/messages', [AdminDisputeController::class, 'messages'])->name('disputes.messages');
    Route::post('/disputes/{booking}/notes', [AdminDisputeController::class, 'addNote'])->name('disputes.notes');
    Route::post('/disputes/{booking}/resolve', [AdminDisputeController::class, 'resolve'])->name('disputes.resolve');

    // 8.3 — Users: warnings & suspension
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::post('/users/{user}/warnings', [AdminUserController::class, 'createWarning'])->name('users.warnings');
    Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend'])->name('users.suspend');
    Route::post('/users/{user}/unsuspend', [AdminUserController::class, 'unsuspend'])->name('users.unsuspend');

    // 8.4 / 8.5 — Alerts
    Route::get('/alerts', [AdminAlertController::class, 'index'])->name('alerts.index');
    Route::post('/alerts/{alert}/resolve', [AdminAlertController::class, 'resolve'])->name('alerts.resolve');
    Route::post('/alerts/{alert}/dismiss', [AdminAlertController::class, 'dismiss'])->name('alerts.dismiss');

    // 8.6 — Team delegation
    Route::get('/team', [AdminUserController::class, 'team'])->name('team.index');
    Route::post('/team', [AdminUserController::class, 'createCollaborator'])->name('team.store');
    Route::put('/team/{user}', [AdminUserController::class, 'updateCollaboratorRole'])->name('team.update');
    Route::delete('/team/{user}', [AdminUserController::class, 'revokeCollaborator'])->name('team.destroy');

    // 8.8 — Operations (check-in tracking)
    Route::get('/operations', [AdminOperationsController::class, 'index'])->name('operations.index');

    // 8.9 — Review moderation
    Route::get('/reviews/reported', [AdminReviewModerationController::class, 'reported'])->name('reviews.reported');
    Route::post('/reviews/{review}/approve', [AdminReviewModerationController::class, 'approve'])->name('reviews.approve');
    Route::delete('/reviews/{review}', [AdminReviewModerationController::class, 'destroy'])->name('reviews.destroy');
    Route::patch('/reviews/{review}', [AdminReviewModerationController::class, 'update'])->name('reviews.update');

    // 8.10 — Audit trail
    Route::get('/audit', [AdminAuditController::class, 'index'])->name('audit.index');

    // 8.12 — KPIs
    Route::get('/kpis', [AdminKpiController::class, 'index'])->name('kpis.index');
});
