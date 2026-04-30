<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/products', [ProductController::class, 'index']);

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // Benchmark APIs
    Route::post('/benchmark/checkout/before', [OrderController::class, 'before']);
    Route::post('/benchmark/checkout/compare', [OrderController::class, 'compare']);

    Route::post('/benchmark/checkout/after', [OrderController::class, 'after'])
        ->middleware('throttle:2000,10');

    // Inventory / Report System (RabbitMQ)
    Route::get('/generate-inventory-report', [OrderController::class, 'generateDailyReport']);

    Route::get('/inventory-report', [OrderController::class, 'generateDailyReport']);

    Route::get('/report/sync-bad-way', [OrderController::class, 'generateReportSync']);
});