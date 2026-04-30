<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OnlinePaymentsController;
use App\Http\Controllers\InPersonPaymentsController;
use App\Http\Controllers\IprocessWebhookController;
use App\Http\Controllers\ClientsGhlController;
use App\Http\Controllers\GhlBridgeWebhookController;
use App\Http\Controllers\GhlOAuthController;
use App\Http\Controllers\MarketplaceWorkflowSubscriptionController;
use App\Http\Controllers\NmiWebhookController;
use App\Http\Controllers\SalesRepsController;
use App\Http\Controllers\ReportingController;
use App\Http\Controllers\AccountSettingsController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/oauth/connect', [GhlOAuthController::class, 'connect'])->name('oauth.connect');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/merchant-management', [DashboardController::class, 'index'])->name('merchant-management');
    Route::get('/online-payments', [OnlinePaymentsController::class, 'index'])->name('online-payments');
    Route::post('/online-payments/orders', [OnlinePaymentsController::class, 'storeOrder'])->name('online-payments.orders.store');
    Route::post('/online-payments/charge', [OnlinePaymentsController::class, 'charge'])->name('online-payments.charge');
    Route::get('/in-person-payments', [InPersonPaymentsController::class, 'index'])->name('in-person-payments');
    Route::permanentRedirect('/clients-ghl', '/clients');
    Route::get('/clients', [ClientsGhlController::class, 'index'])->name('clients');
    Route::post('/clients/sync-locations', [ClientsGhlController::class, 'syncLocations'])->name('clients.sync-locations');
    Route::post('/clients/pit', [ClientsGhlController::class, 'connectPit'])->name('clients.pit');
    Route::post('/clients/location', [ClientsGhlController::class, 'saveLocation'])->name('clients.location');
    Route::post('/clients/sync', [ClientsGhlController::class, 'sync'])->name('clients.sync');
    Route::get('/sales-reps', [SalesRepsController::class, 'index'])->name('sales-reps');
    Route::get('/reporting', [ReportingController::class, 'index'])->name('reporting');
    Route::get('/account-settings', [AccountSettingsController::class, 'index'])->name('account-settings');
});

Route::get('/oauth/callback', [GhlOAuthController::class, 'callback'])->name('oauth.callback');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::post('/webhooks/nmi', NmiWebhookController::class)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('webhooks.nmi');
Route::post('/webhooks/ghl/orders', GhlBridgeWebhookController::class)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('webhooks.ghl.orders');
Route::post('/webhooks/iprocess/payments', IprocessWebhookController::class)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('webhooks.iprocess.payments');
Route::post('/marketplace/workflows/subscription', MarketplaceWorkflowSubscriptionController::class)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('marketplace.workflows.subscription');

require __DIR__.'/auth.php';
