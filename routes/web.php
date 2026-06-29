<?php

use App\Http\Controllers\DashboardController;
use App\Http\Middleware\RequireDashboardBasicAuth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::middleware(RequireDashboardBasicAuth::class)->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/rooms', [DashboardController::class, 'rooms'])->name('dashboard.rooms');
    Route::get('/dashboard/roll-call', [DashboardController::class, 'rollCall'])->name('dashboard.roll-call');
    Route::get('/dashboard/payments', [DashboardController::class, 'payments'])->name('dashboard.payments');
    Route::get('/dashboard/settings', [DashboardController::class, 'settings'])->name('dashboard.settings');
});
