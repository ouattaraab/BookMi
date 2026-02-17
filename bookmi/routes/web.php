<?php

use App\Http\Controllers\Web\TalentPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/talent/{slug}', [TalentPageController::class, 'show'])->name('talent.show');
