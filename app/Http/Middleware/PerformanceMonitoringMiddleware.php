<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PerformanceMonitoringMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // ================== TRACING ==================
        $traceId = Str::uuid()->toString();

        // حفظ داخل attributes (request1-clean)
        $request->attributes->set('trace_id', $traceId);
        $request->attributes->set('request_id', $traceId);

        // حفظ داخل headers (main)
        $request->headers->set('X-Trace-Id', $traceId);

        // ================== MODE DETECTION ==================
        $isAfterMode = str_contains($request->path(), 'after') 
            || $request->query('mode') === 'after';

        $executionLabel = $isAfterMode 
            ? 'AFTER (Optimized)' 
            : 'BEFORE (Vulnerable)';

        // ================== PERFORMANCE START ==================
        $startTime = hrtime(true);
        $startMemory = memory_get_usage();

        try {
            // ================== BUSINESS LOGIC ==================
            $response = $next($request);
        } catch (\Throwable $exception) {

            // ================== ERROR HANDLING (MERGED) ==================
            Log::error('aop_exception_caught', [
                'trace_id' => $traceId,
                'exception' => class_basename($exception),
                'message' => $exception->getMessage(),
                'path' => $request->path(),
                'method' => $request->method(),
                'mode' => $executionLabel
            ]);

            throw $exception;
        }

        // ================== PERFORMANCE CALC ==================
        $duration = (hrtime(true) - $startTime) / 1_000_000;

        // request1-clean (peak memory)
        $peakMemory = memory_get_peak_usage(true) / 1024 / 1024;

        // main (delta memory)
        $memoryUsage = (memory_get_usage() - $startMemory) / 1024;

        // ================== LOGGING (MERGED FULL) ==================
        Log::info('aop_request_metrics', [
            'trace_id' => $traceId,
            'method' => $request->method(),
            'path' => $request->path(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => round($duration, 2),
            'peak_memory_mb' => round($peakMemory, 2),
            'memory_usage_kb' => round($memoryUsage, 2),
            'mode' => $executionLabel,
            'user_id' => $request->user()?->id ?? 'anonymous',
            'timestamp' => now()->toIso8601String(),
        ]);

        // ================== RESPONSE ENRICHMENT ==================
        if ($response instanceof JsonResponse) {
            $payload = $response->getData(true) ?? [];

            if (is_array($payload)) {

                // request1-clean style
                $payload['_aop_metrics'] = [
                    'trace_id' => $traceId,
                    'duration_ms' => round($duration, 2),
                    'peak_memory_mb' => round($peakMemory, 2),
                    'memory_usage_kb' => round($memoryUsage, 2),
                ];

                // main style
                $payload['comparison_metrics'] = [
                    'mode' => $executionLabel,
                    'execution_time' => round($duration, 2) . ' ms',
                    'memory_usage' => round($memoryUsage, 2) . ' KB',
                    'parallel_id' => $traceId
                ];

                // efficiency report
                $payload['efficiency_report'] = $isAfterMode
                    ? "Resource locking and optimization active."
                    : "Warning: Parallel race conditions may occur.";

                $response->setData($payload);
            }
        }

        // ================== HEADERS ==================
        return $response->header('X-Trace-Id', $traceId);
    }
}