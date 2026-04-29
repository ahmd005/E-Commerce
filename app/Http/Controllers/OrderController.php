<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Exception;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function checkout(Request $request)
    {
        return DB::transaction(function () use ($request) {

            $user = $request->user();
            $cart = Cart::where('user_id', $user->id)->first();

            $order = Order::create([
                'user_id' => $user->id,
                'total_price' => 0,
                'status' => 'pending'
            ]);

            $total = 0;

            foreach ($cart->items as $item) {

                $product = Product::lockForUpdate()->find($item->product_id);

                if ($product->stock < $item->quantity) {
                    throw new Exception("Out of stock");
                }

                $product->stock -= $item->quantity;
                $product->save();

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item->quantity,
                    'price' => $product->price
                ]);

                $total += $product->price * $item->quantity;
            }

            $order->update(['total_price' => $total]);

            $cart->items()->delete();

            return $order;
        });
    }

    public function index(Request $request) {
        return Order::where('user_id', $request->user()->id)->get();
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
