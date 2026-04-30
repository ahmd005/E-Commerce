<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * PerformanceMonitoringMiddleware - AOP Layer (Cross-Cutting Concerns)
 * 
 * مسؤولة عن:
 * 1. قياس أداء الطلب (duration) - Performance Monitoring
 * 2. توليد Tracing ID لتتبع الطلب - Distributed Tracing
 * 3. تسجيل معلومات الطلب والاستجابة - Logging
 * 4. معالجة الأخطاء المركزية - Centralized Error Handling
 * 
 * هذه الطبقة تفصل جميع Cross-Cutting Concerns عن Business Logic
 * لا يجب أن يعتمد Services أو Controllers على هذه المسؤوليات
 */
class PerformanceMonitoringMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // ============ AOP: TRACING ============
        $traceId = Str::uuid()->toString();
        $request->attributes->set('request_id', $traceId);
        $request->attributes->set('trace_id', $traceId);

        // ============ AOP: PERFORMANCE MONITORING - قياس الوقت ============
        $startTime = hrtime(true);
        $startMemory = memory_get_usage();

        try {
            // ============ BUSINESS LOGIC ============
            $response = $next($request);
        } catch (\Throwable $exception) {
            // ============ AOP: CENTRALIZED ERROR HANDLING ============
            Log::error('aop_exception_caught', [
                'trace_id' => $traceId,
                'exception' => class_basename($exception),
                'message' => $exception->getMessage(),
                'path' => $request->path(),
                'method' => $request->method(),
            ]);
            throw $exception;
        }

        // ============ AOP: PERFORMANCE MONITORING - حساب النتائج ============
        $duration = (hrtime(true) - $startTime) / 1_000_000;
        $peakMemory = memory_get_peak_usage(true) / 1024 / 1024;

        // ============ AOP: LOGGING - تسجيل المقاييس ============
        Log::info('aop_request_metrics', [
            'trace_id' => $traceId,
            'method' => $request->method(),
            'path' => $request->path(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => round($duration, 2),
            'peak_memory_mb' => round($peakMemory, 2),
            'user_id' => $request->user()?->id ?? 'anonymous',
            'timestamp' => now()->toIso8601String(),
        ]);

        // ============ AOP: INJECT METRICS INTO RESPONSE ============
        if ($response instanceof JsonResponse) {
            $payload = $response->getData(true) ?? [];
            if (is_array($payload)) {
                $payload['_aop_metrics'] = [
                    'trace_id' => $traceId,
                    'duration_ms' => round($duration, 2),
                    'memory_mb' => round($peakMemory, 2),
                ];
                $response->setData($payload);
            }
        }

        // ============ AOP: INJECT TRACE ID INTO HEADERS ============
        return $response->header('X-Trace-Id', $traceId);
    }
}