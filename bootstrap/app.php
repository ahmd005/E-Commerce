<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\HighPerformanceTransactionAspect;

use App\Http\Middleware\PerformanceMonitoringMiddleware;
use App\Http\Middleware\QueueRequestTrackingMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        $middleware->alias([
            'high-performance' => \App\Http\Middleware\HighPerformanceTransactionAspect::class,
        ]);
    
        /*
        |--------------------------------------------------------------------------
        | Middleware Stack
        |--------------------------------------------------------------------------
        */

        
        // 1. Performance Monitoring (AOP + tracing + logging)
        $middleware->append(PerformanceMonitoringMiddleware::class);

        // 2. Queue Tracking Middleware alias (from nadim branch)
        $middleware->alias([
            'track.queue' => QueueRequestTrackingMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();