<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');


Route::get('/products', [ProductController::class, 'index']);


Route::middleware('auth:sanctum')->group(function () {

    
    Route::post('/benchmark/checkout/before', [OrderController::class, 'before']);

    Route::post('/benchmark/checkout/compare', [OrderController::class, 'compare']);

    Route::post('/benchmark/checkout/after', [OrderController::class, 'after'])
    ->middleware('throttle:2000,10');
    
});