<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

// ---- Public ----
Route::get('/reviews', [ReviewController::class, 'index']);
Route::post('/reviews', [ReviewController::class, 'store'])->middleware('throttle:5,60');

Route::post('/orders', [OrderController::class, 'store'])->middleware('throttle:10,60');
Route::post('/orders/{order}/pay/paystack/init', [OrderController::class, 'payInit']);
Route::get('/orders/{order}/status', [OrderController::class, 'status']);
Route::post('/webhooks/paystack', [OrderController::class, 'webhook']);
Route::get('/config/public', [OrderController::class, 'publicConfig']);

// ---- Admin (shared-secret token, see App\Http\Middleware\RequireAdminToken) ----
Route::middleware('admin.token')->prefix('admin')->group(function () {
    Route::get('/reviews', [ReviewController::class, 'adminIndex']);
    Route::post('/reviews/{review}/approve', [ReviewController::class, 'approve']);
    Route::post('/reviews/{review}/reject', [ReviewController::class, 'reject']);
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);

    Route::get('/orders', [OrderController::class, 'adminIndex']);
    Route::get('/orders/{order}', [OrderController::class, 'adminShow']);
    Route::post('/orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::post('/orders/{order}/mark-paid-manually', [OrderController::class, 'markPaidManually']);
});
