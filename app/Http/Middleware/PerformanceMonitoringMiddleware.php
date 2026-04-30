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
        $traceId = Str::uuid()->toString();
        $startTime = hrtime(true);
        $startMemory = memory_get_usage();

        $request->headers->set('X-Trace-Id', $traceId);

        $isAfterMode = str_contains($request->path(), 'after') || $request->query('mode') === 'after';
        $executionLabel = $isAfterMode ? 'AFTER (Optimized)' : 'BEFORE (Vulnerable)';

        try {
            $response = $next($request);
        } catch (\Throwable $exception) {
        
            Log::error("AOP Exception [$executionLabel]", [
                'trace_id' => $traceId,
                'message'  => $exception->getMessage(),
                'path'     => $request->path(),
            ]);

            throw $exception;
        }

      
        $duration = (hrtime(true) - $startTime) / 1_000_000;
        $memoryUsage = memory_get_usage() - $startMemory;

        $context = [
            'trace_id'           => $traceId,
            'label'              => $executionLabel,
            'duration_ms'        => round($duration, 2),
            'memory_usage_kb'    => round($memoryUsage / 1024, 2),
            'status_code'        => $response->getStatusCode(),
        ];

       
        Log::info("AOP Metrics for $executionLabel", $context);

       
        if ($response instanceof JsonResponse) {
            $payload = $response->getData(true);
            
            $payload['comparison_metrics'] = [
                'mode'           => $executionLabel,
                'execution_time' => $context['duration_ms'] . ' ms',
                'memory_usage'   => $context['memory_usage_kb'] . ' KB',
                'parallel_id'    => $context['trace_id']
            ];
            
           
            if ($isAfterMode) {
                $payload['efficiency_report'] = "Resource locking and management is active.";
            } else {
                $payload['efficiency_report'] = "Warning: Parallel race conditions may occur.";
            }

            $response->setData($payload);
        }

        return $response->header('X-Trace-Id', $traceId);
    }
}