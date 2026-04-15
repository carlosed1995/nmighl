<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OnlinePaymentsController;
use App\Http\Controllers\InPersonPaymentsController;
use App\Http\Controllers\ClientsGhlController;
use App\Http\Controllers\GhlOAuthController;
use App\Http\Controllers\SalesRepsController;
use App\Http\Controllers\ReportingController;
use App\Http\Controllers\AccountSettingsController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/oauth/connect', [GhlOAuthController::class, 'connect'])->name('oauth.connect');
    Route::get('/oauth/callback', [GhlOAuthController::class, 'callback'])->name('oauth.callback');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/merchant-management', [DashboardController::class, 'index'])->name('merchant-management');
    Route::get('/online-payments', [OnlinePaymentsController::class, 'index'])->name('online-payments');
    Route::get('/in-person-payments', [InPersonPaymentsController::class, 'index'])->name('in-person-payments');
    Route::get('/clients-ghl', [ClientsGhlController::class, 'index'])->name('clients-ghl');
    Route::post('/clients-ghl/sync', [ClientsGhlController::class, 'sync'])->name('clients-ghl.sync');
    Route::get('/sales-reps', [SalesRepsController::class, 'index'])->name('sales-reps');
    Route::get('/reporting', [ReportingController::class, 'index'])->name('reporting');
    Route::get('/account-settings', [AccountSettingsController::class, 'index'])->name('account-settings');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
