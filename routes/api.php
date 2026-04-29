<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;




Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//  
   Route::post('/register', [AuthController::class, 'register']);
   Route::post('/login', [AuthController::class, 'login']);
   Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

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
    Route::get('/orders', [OrderController::class, 'index']);
});





// الراوت الخاص بتقرير الجرد اليومي
    // مسار توليد التقرير الضخم عبر RabbitMQ
 //Route::get('/inventory-report', [OrderController::class, 'generateDailyReport']);

  // مسار توليد التقرير الضخم عبر RabbitMQ
Route::get('/generate-inventory-report', [OrderController::class, 'generateDailyReport']);

// 1. الرابط "السيئ" (المعالجة المباشرة والخطيرة)
Route::get('/report/sync-bad-way', [OrderController::class, 'generateReportSync']);