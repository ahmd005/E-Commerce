<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HighPerformanceTransactionAspect
{
    public function handle(Request $request, Closure $next)
    {
        // [AOP - Before Advice]: بدء قياس الوقت وبدء المعاملة
        $startTime = microtime(true);
        DB::beginTransaction();

        try {
            $response = $next($request);

            // [AOP - After Returning]: إذا نجحت العملية، نثبت البيانات (ACID)
            DB::commit();
            
            $executionTime = microtime(true) - $startTime;
            
            // إضافة زمن التنفيذ للرأس (Header) للتقرير لاحقاً
            $response->headers->set('X-Performance-Time', $executionTime);
            
            return $response;

        } catch (\Exception $e) {
            // [AOP - After Throwing]: في حال حدوث أي خطأ، تراجع عن كل شيء (Atomicity)
            DB::rollBack();
            
            Log::error("Transaction Failed: " . $e->getMessage());
            return response()->json(['error' => 'Transaction Integrity Violation', 'details' => $e->getMessage()], 500);
        }
    }
}



// & "C:\Program Files\k6\k6.exe" run -e MODE=safe -e USERS=100 -e PRODUCT_ID=2 -e STOCK=100 -e QUANTITY=1 compare-test.js
// & "C:\Program Files\k6\k6.exe" run -e MODE=unsafe -e USERS=100 -e PRODUCT_ID=2 -e STOCK=100 -e QUANTITY=1 compare-test.js
