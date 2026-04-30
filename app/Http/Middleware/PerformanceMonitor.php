<?php

namespace App\Http\Middleware; // هذا السطر هو الأهم لحل مشكلة Undefined type

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PerformanceMonitor
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);
        $response = $next($request);
        $duration = (microtime(true) - $start) * 1000;

        // رصد ذروة استهلاك الذاكرة لضمان ظهور الفرق في الـ Logs
        $peakMemory = round(memory_get_peak_usage() / 1024 / 1024, 2);

        Log::info("AOP_MONITOR: [{$request->method()}] {$request->path()} - Time: " . round($duration, 2) . "ms - Peak Memory: {$peakMemory}MB");

        return $response;
    }
}