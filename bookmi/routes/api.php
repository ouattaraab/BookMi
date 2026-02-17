<?php

use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\HealthCheckController;
use App\Http\Controllers\Api\V1\TalentController;
use App\Http\Controllers\Api\V1\ServicePackageController;
use App\Http\Controllers\Api\V1\TalentProfileController;
use App\Http\Controllers\Api\V1\VerificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('/health', HealthCheckController::class)->name('health');

    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');

    Route::get('/talents', [TalentController::class, 'index'])->name('talents.index');
    Route::get('/talents/{slug}', [TalentController::class, 'show'])->name('talents.show');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/talent_profiles', [TalentProfileController::class, 'store'])->name('talent_profiles.store');
        Route::get('/talent_profiles/me', [TalentProfileController::class, 'showOwn'])->name('talent_profiles.me');
        Route::patch('/talent_profiles/{talent_profile}', [TalentProfileController::class, 'update'])->name('talent_profiles.update');
        Route::delete('/talent_profiles/{talent_profile}', [TalentProfileController::class, 'destroy'])->name('talent_profiles.destroy');

        Route::post('/verifications', [VerificationController::class, 'store'])->name('verifications.store');
        Route::get('/verifications/me', [VerificationController::class, 'showOwn'])->name('verifications.me');

        Route::apiResource('service_packages', ServicePackageController::class)->except(['show']);
    });
});
