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

//Route::post('/register', [AuthController::class, 'register']);
//Route::post('/login', [AuthController::class, 'login']);
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');


Route::get('/products', [ProductController::class, 'index']);



Route::middleware('auth:sanctum')->group(function () {


 // Products
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);

    // Cart
    Route::get('/cart', [CartController::class, 'getCart']);
    Route::post('/cart/add', [CartController::class, 'add']);
    Route::post('/cart/update', [CartController::class, 'update']);
    Route::post('/cart/remove', [CartController::class, 'remove']);

    // Orders
    Route::post('/checkout', [OrderController::class, 'checkout']);


    Route::post('/benchmark/checkout/before', [OrderController::class, 'before']);

    Route::post('/benchmark/checkout/compare', [OrderController::class, 'compare']);

    Route::post('/benchmark/checkout/after', [OrderController::class, 'after'])
    ->middleware('throttle:2000,10');

    
});


// الراوت الخاص بتقرير الجرد اليومي
    // مسار توليد التقرير الضخم عبر RabbitMQ
 //Route::get('/inventory-report', [OrderController::class, 'generateDailyReport']);

  // مسار توليد التقرير الضخم عبر RabbitMQ
Route::get('/generate-inventory-report', [OrderController::class, 'generateDailyReport']);

// 1. الرابط "السيئ" (المعالجة المباشرة والخطيرة)
Route::get('/report/sync-bad-way', [OrderController::class, 'generateReportSync']);