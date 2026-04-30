<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Exception;
use App\Jobs\ProcessOrder; 


use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{

public function before(Request $request) {
    $product = Product::find(1); 
    $currentStock = $product->stock;

     usleep(500000); 

    if ($currentStock > 0) {
        $product->stock = $currentStock - 1;
        $product->save();

        return response()->json([
            'status' => 'VULNERABLE_SUCCESS',
            'stock_after' => $product->stock  
        ]);
    }

    return response()->json(['status' => 'OUT_OF_STOCK'], 400);
}


public function after(Request $request) {
    $productId = $request->product_id;

   
    $pool = Cache::lock('my_thread_pool', 10); 

    try {
        return $pool->block(5, function () use ($productId) {
            
            return DB::transaction(function () use ($productId) {
                $product = Product::lockForUpdate()->find($productId);
                
                if ($product && $product->stock > 0) {
                    $product->decrement('stock');
                    
                    return response()->json([
                        'status' => 'SAFE_SUCCESS',
                        'stock_after' => $product->stock,
                        'info' => 'Processed via Thread Pool'
                    ]);
                }
                
                return response()->json(['status' => 'OUT_OF_STOCK'], 400);
            });
        });
    } catch (Exception $e) {
        return response()->json([
            'status' => 'SERVER_BUSY',
            'message' => 'Thread Pool is full, try again later'
        ], 503);
    }


// public function generateDailyReport(Request $request) {
//    // set_time_limit(0);
//     $useBatch = $request->query('use_batch', 'false') === 'true';
//     $startTime = microtime(true);
//     $initialMemory = memory_get_usage();

//     if ($useBatch) {
//         // المسار الصحيح (After): استخدام الدفعات لتقليل استهلاك الذاكرة
//         Order::where('status', 'completed')->chunk(100, function ($orders) {
//             foreach ($orders as $order) {
//                 // محاكاة معالجة بيانات (مثل حساب الأرباح)
//                 usleep(1000); // محاكاة ضغط بسيط
//             }
//         });
//     } else {
//         // المسار الخاطئ (Before): جلب كل البيانات دفعة واحدة
//         $orders = Order::all(); 
//         foreach ($orders as $order) {
//             usleep(1000);
//         }
//     }

//     $executionTime = (microtime(true) - $startTime) * 1000; // بالملي ثانية
//     $memoryUsed = (memory_get_usage() - $initialMemory) / 1024 / 1024; // بالميغابايت

//     return response()->json([
//         'mode' => $useBatch ? 'Batch Processing' : 'Standard (All)',
//         'execution_time_ms' => round($executionTime, 2),
//         'memory_usage_mb' => round($memoryUsed, 2),
//         'orders_count' => Order::count(),
//         'orders_count' => \App\Models\Order::count()

//     ]);
// }



public function generateDailyReport(Request $request) 
{
    // تسجيل وقت بداية الإرسال (وليس وقت المعالجة)
    $startTime = microtime(true);

    // الخطوة الجوهرية (المحاضرة 3):
    // بدلاً من معالجة البيانات هنا وجعل المستخدم ينتظر، نرسل "رسالة" لـ RabbitMQ
    // الـ Job (ProcessInventoryReport) هو من سيقوم بالـ Chunking لاحقاً
    \App\Jobs\ProcessInventoryReport::dispatch();

    $executionTime = (microtime(true) - $startTime) * 1000;

    // الرد الفوري للمستخدم (Asynchronous Communication)
    return response()->json([
        'message' => 'تم إرسال طلب التقرير إلى RabbitMQ بنجاح.',
        'mode' => 'Asynchronous (Background Processing)',
        'dispatch_time_ms' => round($executionTime, 2),
        'status' => 'Queued'
    ]);
}



//http://localhost:15672     guest   php artisan queue:work



public function generateReportSync() 
{
    // تسجيل استهلاك الذاكرة والوقت قبل البدء
    $startMemory = memory_get_usage();
    $startTime = microtime(true);

    // الكارثة التقنية: تحميل كل السجلات دفعة واحدة في الذاكرة
    // (هنا لا نستخدم Chunk ولا نستخدم RabbitMQ)
    $orders = \App\Models\Order::all(); 

    foreach ($orders as $order) {
        // محاكاة معالجة ثقيلة لكل سجل (تأخير 0.01 ثانية مثلاً)
        usleep(10000); 
    }

    $endMemory = memory_get_usage();
    $executionTime = microtime(true) - $startTime;
 
    
    // حساب الذاكرة المستهلكة أثناء هذه العملية فقط
    $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024;

    return response()->json([
        'status' => 'Finished',
        'mode' => 'Synchronous (The Bad Way)',
        'details' => [
            'wait_time_for_user' => round($executionTime, 2) . ' seconds',
            'memory_peak' => round($memoryUsed, 2) . ' MB',
            'total_records_processed' => $orders->count()
        ],
        'note' => 'المستخدم اضطر لانتظار كل هذا الوقت قبل رؤية هذه الرسالة!'
    ]);
}

}


    public function resetStock(Request $request)
    {
        $productId = $request->product_id ?? 1;
        $product = Product::find($productId);
        
        if ($product) {
            $product->update(['stock' => 10]);
            return response()->json(['message' => 'Stock reset to 10 successfully']);
        }
        
        return response()->json(['message' => 'Product not found'], 404);
    }

}
