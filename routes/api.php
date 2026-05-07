<?php

use App\Http\Controllers\Api\GhlContactController;
use App\Http\Controllers\Api\GhlInvoiceController;
use App\Http\Controllers\Api\GhlLocationController;
use App\Http\Middleware\VerifyGhlWebhookSecret;
use Illuminate\Support\Facades\Route;

Route::middleware(VerifyGhlWebhookSecret::class)
    ->prefix('ghl')
    ->group(function () {
        Route::post('locations', [GhlLocationController::class, 'upsert']);
        Route::delete('locations/{ghlId}', [GhlLocationController::class, 'destroy']);

        Route::post('contacts', [GhlContactController::class, 'upsert']);
        Route::delete('contacts/{ghlContactId}', [GhlContactController::class, 'destroy']);

        Route::post('invoices', [GhlInvoiceController::class, 'upsert']);
        Route::patch('invoices/{ghlInvoiceId}/status', [GhlInvoiceController::class, 'updateStatus']);
        Route::delete('invoices/{ghlInvoiceId}', [GhlInvoiceController::class, 'destroy']);
    });
