<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ConcurrencyCheckoutController;

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// مهم جداً لحل مشكلة Route [login] not defined
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');


/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/products', [ProductController::class, 'index']);


/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // Products
    Route::post('/products', [ProductController::class, 'store']);

    // Cart
    Route::get('/cart', [CartController::class, 'getCart']);
    Route::post('/cart/add', [CartController::class, 'add']);
    Route::post('/cart/update', [CartController::class, 'update']);
    Route::post('/cart/remove', [CartController::class, 'remove']);

    // Orders
    Route::post('/checkout', [OrderController::class, 'checkout']);
    Route::get('/orders', [OrderController::class, 'index']);

    // Inventory test (بدون throttle)
    Route::post('/test-stock', [InventoryController::class, 'testStock'])
        ->withoutMiddleware([\Illuminate\Routing\Middleware\ThrottleRequests::class]);

    // ========================
    // Benchmark (من الفرع الثاني)
    // ========================
    Route::post('/benchmark/checkout/before', [OrderController::class, 'before']);
    Route::post('/benchmark/checkout/compare', [OrderController::class, 'compare']);
    Route::post('/benchmark/checkout/after', [OrderController::class, 'after'])
        ->middleware('throttle:2000,10');

    // (اختياري إذا بدك ترجعها)
    // Route::post('/checkout/concurrency', [ConcurrencyCheckoutController::class, 'checkout']);
});


/*
|--------------------------------------------------------------------------
| Reports (RabbitMQ + Sync)
|--------------------------------------------------------------------------
*/

// تقرير عبر RabbitMQ
Route::get('/generate-inventory-report', [OrderController::class, 'generateDailyReport']);

// الطريقة السيئة (sync)
Route::get('/report/sync-bad-way', [OrderController::class, 'generateReportSync']);