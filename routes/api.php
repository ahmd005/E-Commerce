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
use App\Http\Controllers\Admin\ActionForAdmin;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Middleware\TrackCartRequests;
use App\Http\Controllers\CartDBController;
use App\Http\Middleware\QueueRequestTrackingMiddleware;
/*
|--------------------------------------------------------------------------
| User Auth Route
|--------------------------------------------------------------------------
*/

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| Auth Routes (PUBLIC)
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/products', [ProductController::class, 'index']);
Route::post('/test-stock', [InventoryController::class, 'testStock']);
Route::post('/benchmark/reset-stock', [OrderController::class, 'resetStock']);
Route::post('/benchmark/bootstrap-stock', [OrderController::class, 'bootstrapStock']);

/*
|--------------------------------------------------------------------------
| Admin Routes (MUST BE AUTH ONLY - separate group)
|--------------------------------------------------------------------------
*/

Route::middleware(QueueRequestTrackingMiddleware::class,'auth:sanctum')->group(function () {
    Route::post('/admin/export/queue', [ActionForAdmin::class, 'exportWithQueue']);
    Route::post('/admin/export/sync', [ActionForAdmin::class, 'exportSync']);
});

/*
|--------------------------------------------------------------------------
| Protected Routes (User)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // Products
    Route::post('/products', [ProductController::class, 'store']);

    

    // Orders
    Route::post('/checkout/legacy', [OrderController::class, 'checkout']);
    Route::get('/orders', [OrderController::class, 'index']);

    // Benchmark
    Route::post('/benchmark/checkout/before', [OrderController::class, 'before']);
    Route::post('/benchmark/checkout/compare', [OrderController::class, 'compare']);
    // Route::post('/benchmark/checkout/after', [OrderController::class, 'after'])
    //     ->middleware('throttle:2000,10');

     Route::post('/benchmark/checkout/after', [OrderController::class, 'after'])
    ->middleware('throttle:benchmark_limit');


    // Optional concurrency
    // Route::post('/checkout/concurrency', [ConcurrencyCheckoutController::class, 'checkout']);
});

/*
|--------------------------------------------------------------------------
| Reports
|--------------------------------------------------------------------------
*/
Route::prefix('v1/cart/redis')
    ->middleware([TrackCartRequests::class, 'auth:sanctum'])
    ->group(function () {
        
        Route::get('/index', [CartController::class, 'index'])->name('api.cart.index');
        
        Route::post('/items', [CartController::class, 'store'])->name('api.cart.store');
        
        Route::delete('/items/{productId}', [CartController::class, 'destroy'])->name('api.cart.destroy');
        Route::delete('/clear', [CartController::class, 'clear'])->name('api.cart.clear');
        
    });
Route::prefix('v1/cart/db')
    ->middleware([TrackCartRequests::class, 'auth:sanctum'])->group(function () {
    Route::get('/index', [CartDBController::class, 'index']);         
    Route::post('/add', [CartDBController::class, 'store']);       
    Route::delete('/remove/{productId}', [CartDBController::class, 'destroy']); 
    Route::delete('/clear', [CartDBController::class, 'clear']);
});
Route::get('/generate-inventory-report', [OrderController::class, 'generateDailyReport']);
Route::get('/report/sync-bad-way', [OrderController::class, 'generateReportSync']);

Route::post('/checkout', [CheckoutController::class, 'store']);
Route::post('/checkout/unsafe', [CheckoutController::class, 'storeUnsafe']);
