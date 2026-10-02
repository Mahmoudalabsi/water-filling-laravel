<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\ElectricityController;
use App\Http\Controllers\SettingsController;

// Auth routes (guest only)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect('/dashboard'));
    Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');

    // Families (JSON API — used by the dashboard's Vue/Alpine components)
    Route::post('/api/families', [FamilyController::class, 'store']);
    Route::delete('/api/families/{id}', [FamilyController::class, 'destroy']);

    // Filling sessions
    Route::get('/api/sessions', [SessionController::class, 'index']);
    Route::post('/api/sessions', [SessionController::class, 'start']);
    Route::put('/api/sessions', [SessionController::class, 'stop']);
    Route::patch('/api/sessions/reset', [SessionController::class, 'reset']);

    // Electricity readings + estimate-price
    Route::get('/api/electricity', [ElectricityController::class, 'index']);
    Route::post('/api/electricity', [ElectricityController::class, 'store']);
    Route::delete('/api/electricity/{id}', [ElectricityController::class, 'destroy']);
    Route::get('/api/electricity/estimate-price', [ElectricityController::class, 'estimatePrice']);

    // Settings
    Route::get('/api/settings', [SettingsController::class, 'index']);
    Route::put('/api/settings', [SettingsController::class, 'update']);
});
