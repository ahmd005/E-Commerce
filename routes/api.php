<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\ActionForAdmin;
use App\Http\Middleware\QueueErrorTrackingMiddleware1;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//  
   Route::post('/register', [AuthController::class, 'register']);
   Route::post('/login', [AuthController::class, 'login']);
   Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/admin/export/queue', [ActionForAdmin::class, 'exportWithQueue']);
    Route::post('/admin/export/sync', [ActionForAdmin::class, 'exportSync']);
});
// Route::prefix('admin/export')->group(function () {
//     Route::post('/sync', [ActionForAdmin::class, 'exportSync']); // بدون طابور
//     Route::post('/queue', [ActionForAdmin::class, 'exportWithQueue']); // مع طابور
//     Route::get('/status/{exportId}', [ActionForAdmin::class, 'checkExportStatus']); //[cite: 1]
// });
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



