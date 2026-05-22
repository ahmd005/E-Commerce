<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QueueRequestTrackingMiddleware
{
    /**
     * الأنماط التي نريد تتبعها (تطبيقات الـ Queue فعلياً)
     */
    protected $trackedPaths = [
        'api/admin/export*',      // تصدير المستخدمين
        'api/queue/*',      // endpoints المخصصة للـ Queue
        'api/jobs/*',       // مراقبة الـ Jobs
    ];
    
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // ✅ تحسين 1: تتبع فقط الطلبات المتعلقة بـ Queue
        if (!$this->shouldTrack($request)) {
            return $next($request);
        }
        
        $startTime = microtime(true);
        $requestId = $this->generateRequestId();
        
        $request->attributes->set('queue_request_id', $requestId);
        $request->attributes->set('queue_start_time', $startTime);

        // ✅ تحسين 2: تسجيل بداية الطلب بمعلومات أكثر فائدة
        $this->logQueueRequestStart($request, $requestId);

        try {
            $response = $next($request);
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            // ✅ تحسين 3: تسجيل معلومات الـ Queue إذا وجدت
            $queueInfo = $this->getQueueInfoFromRequest($request);
            
            $this->logQueueRequestEnd($request, $requestId, $duration, $response->status(), $queueInfo);
            
            $response->headers->set('X-Queue-Trace-Id', $requestId);
            $response->headers->set('X-Queue-Duration-Ms', $duration);
            
            return $response;
            
        } catch (\Exception $e) {
            // ✅ تحسين 4: تسجيل الاستثناءات بشكل منفصل
            Log::channel('queue_errors')->error('🔥 استثناء في طلب Queue', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }
    
    /**
     * ✅ تحسين مهم جداً: تحديد ما إذا كان الطلب يستحق التتبع
     */
    private function shouldTrack(Request $request): bool
    {
        $path = $request->path();
        
        // تتبع طلبات التصدير فقط
        if (str_contains($path, 'export')) {
            return true;
        }
        
        // تتبع طلبات مراقبة الـ Queue
        if (str_contains($path, 'queue') || str_contains($path, 'job')) {
            return true;
        }
        
        // تتبع طلبات الـ API المحددة في trackedPaths
        foreach ($this->trackedPaths as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * توليد معرف فريد للطلب
     */
    private function generateRequestId(): string
    {
        return 'qreq_' . uniqid() . '_' . bin2hex(random_bytes(4));
    }
    
    /**
     * استخراج معلومات الـ Queue من الطلب (إذا وجدت)
     */
    private function getQueueInfoFromRequest(Request $request): array
    {
        $info = [];
        
        // معلومات Job من الـ Header
        if ($request->hasHeader('X-Job-Id')) {
            $info['job_id'] = $request->header('X-Job-Id');
        }
        
        if ($request->hasHeader('X-Queue-Name')) {
            $info['queue_name'] = $request->header('X-Queue-Name');
        }
        
        // // معلومات من الـ Session
        // if ($request->session()->has('last_export_id')) {
        //     $info['last_export_id'] = $request->session()->get('last_export_id');
        // }
        
        return $info;
    }
    
    /**
     * تسجيل بداية طلب الـ Queue
     */
    private function logQueueRequestStart(Request $request, string $requestId): void
    {
        Log::channel('queue_jobs')->info('📥 بدء طلب', [
            'request_id' => $requestId,
            'endpoint' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => auth()->id() ?? 'guest',
            'user_email' => auth()->user()->email ?? 'guest',
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
    
    /**
     * تسجيل نهاية طلب الـ Queue
     */
    private function logQueueRequestEnd(Request $request, string $requestId, float $duration, int $statusCode, array $queueInfo = []): void
    {
        $logData = [
            'request_id' => $requestId,
            'duration_ms' => $duration,
            'status_code' => $statusCode,
            'timestamp' => now()->toIso8601String(),
        ];
        
        if (!empty($queueInfo)) {
            $logData['queue_info'] = $queueInfo;
        }
        
        if ($statusCode >= 400) {
            Log::channel('queue_errors')->warning('⚠️ فشل الطلب', $logData);
        } else {
            Log::channel('queue_jobs')->info('✅ اكتمل الطلب', $logData);
        }
        
        // ✅ تحسين 5: تسجيل إحصاءات الأداء للمعايرة
        if ($duration > 5000) { // أكثر من 5 ثواني
            Log::channel('queue_errors')->warning('🐌 طلب بطيء', [
                'request_id' => $requestId,
                'duration_ms' => $duration,
                'endpoint' => $request->fullUrl(),
            ]);
        }
    }
}