<?php

use App\Http\Controllers\Web\Manager\BookingController;
use App\Http\Controllers\Web\Manager\DashboardController;
use App\Http\Controllers\Web\Manager\MessageController;
use App\Http\Controllers\Web\Manager\TalentController;
use Illuminate\Support\Facades\Route;

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Talents gérés
Route::get('/talents', [TalentController::class, 'index'])->name('talents');
Route::get('/talents/{id}', [TalentController::class, 'show'])->name('talents.show');

// Réservations agrégées
Route::get('/bookings', [BookingController::class, 'index'])->name('bookings');
Route::get('/bookings/{id}', [BookingController::class, 'show'])->name('bookings.show');

// Messages
Route::get('/messages', [MessageController::class, 'index'])->name('messages');
Route::get('/messages/{id}', [MessageController::class, 'show'])->name('messages.show');
Route::post('/messages/{id}', [MessageController::class, 'send'])->name('messages.send');
