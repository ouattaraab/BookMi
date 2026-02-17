<?php

use App\Http\Controllers\Admin\VerificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/verifications', [VerificationController::class, 'index'])->name('verifications.index');
    Route::get('/verifications/{verification}', [VerificationController::class, 'show'])->name('verifications.show');
    Route::get('/verifications/{verification}/document', [VerificationController::class, 'document'])->name('verifications.document');
    Route::post('/verifications/{verification}/review', [VerificationController::class, 'review'])->name('verifications.review');
});
