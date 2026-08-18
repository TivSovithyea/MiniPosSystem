<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaywayPaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => ['status' => 'ok', 'service' => 'MiniPOS API']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/payments/payway/callback', [PaywayPaymentController::class, 'callback'])->middleware('throttle:60,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/dashboard', DashboardController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show']);
    Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel']);
    Route::get('/orders/{order}/payway-payment', [PaywayPaymentController::class, 'show'])->middleware('throttle:30,1');
    Route::post('/orders/{order}/payway-payment/simulate', [PaywayPaymentController::class, 'simulate'])->middleware('throttle:10,1');
    Route::get('/reports/summary', [ReportController::class, 'summary']);
    Route::get('/reports/products', [ReportController::class, 'products']);
    Route::get('/reports/customers', [ReportController::class, 'customers']);
});
