<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Exception;
use App\Jobs\ProcessOrder; 

use Illuminate\Support\Facades\Log;



// تأكد من استدعاء الـ Job الخاص بالتقارير إذا كنت تستخدمه
// use App\Jobs\UpdateInventoryReport;

class OrderController extends Controller
{

public function before(Request $request) {
    $product = Product::find(1); 
    $currentStock = $product->stock;

     usleep(5000); 

   // if ($currentStock > 0) {
        $product->stock = $currentStock - 1;
        $product->save();

        return response()->json([
            'status' => 'VULNERABLE_SUCCESS',
            'stock_after' => $product->stock  
        ]);
   // }

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

//////////////////////////////////

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






public function generateReportSync() 
{
    // تسجيل استهلاك الذاكرة والوقت قبل البدء
    $startMemory = memory_get_usage();
    $startTime = microtime(true);
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







//////////////////////////////////////////////////////////////////////////////////////



// namespace App\Http\Controllers;

// use App\Models\Cart;
// use App\Models\Order;
// use App\Models\OrderItem;
// use App\Models\Product;
// use Exception;
// use Illuminate\Support\Facades\DB;

// use Illuminate\Http\Request;

// use Illuminate\Support\Facades\Log;

// class OrderController extends Controller
// {
//     public function checkout(Request $request)
//     {
//         return DB::transaction(function () use ($request) {

//             $user = $request->user();
//             $cart = Cart::where('user_id', $user->id)->first();

//             $order = Order::create([
//                 'user_id' => $user->id,
//                 'total_price' => 0,
//                 'status' => 'pending'
//             ]);

//             $total = 0;

//             foreach ($cart->items as $item) {

//                 $product = Product::lockForUpdate()->find($item->product_id);

//                 if ($product->stock < $item->quantity) {
//                     throw new Exception("Out of stock");
//                 }

//                 $product->stock -= $item->quantity;
//                 $product->save();

//                 OrderItem::create([
//                     'order_id' => $order->id,
//                     'product_id' => $product->id,
//                     'quantity' => $item->quantity,
//                     'price' => $product->price
//                 ]);

//                 $total += $product->price * $item->quantity;
//             }

//             $order->update(['total_price' => $total]);

//             $cart->items()->delete();

//             return $order;
//         });
//     }

//     public function index(Request $request) {
//         return Order::where('user_id', $request->user()->id)->get();
//     }


// public function generateDailyReport(Request $request) 
// {
//     // تسجيل وقت بداية الإرسال (وليس وقت المعالجة)
//     $startTime = microtime(true);

//     // الخطوة الجوهرية (المحاضرة 3):
//     // بدلاً من معالجة البيانات هنا وجعل المستخدم ينتظر، نرسل "رسالة" لـ RabbitMQ
//     // الـ Job (ProcessInventoryReport) هو من سيقوم بالـ Chunking لاحقاً
//     \App\Jobs\ProcessInventoryReport::dispatch();

//     $executionTime = (microtime(true) - $startTime) * 1000;

//     // الرد الفوري للمستخدم (Asynchronous Communication)
//     return response()->json([
//         'message' => 'تم إرسال طلب التقرير إلى RabbitMQ بنجاح.',
//         'mode' => 'Asynchronous (Background Processing)',
//         'dispatch_time_ms' => round($executionTime, 2),
//         'status' => 'Queued'
//     ]);
// }






// public function generateReportSync() 
// {
//     // تسجيل استهلاك الذاكرة والوقت قبل البدء
//     $startMemory = memory_get_usage();
//     $startTime = microtime(true);
//     // (هنا لا نستخدم Chunk ولا نستخدم RabbitMQ)
//     $orders = \App\Models\Order::all(); 

//     foreach ($orders as $order) {
//         // محاكاة معالجة ثقيلة لكل سجل (تأخير 0.01 ثانية مثلاً)
//         usleep(10000); 
//     }

//     $endMemory = memory_get_usage();
//     $executionTime = microtime(true) - $startTime;
 
    
//     // حساب الذاكرة المستهلكة أثناء هذه العملية فقط
//     $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024;

//     return response()->json([
//         'status' => 'Finished',
//         'mode' => 'Synchronous (The Bad Way)',
//         'details' => [
//             'wait_time_for_user' => round($executionTime, 2) . ' seconds',
//             'memory_peak' => round($memoryUsed, 2) . ' MB',
//             'total_records_processed' => $orders->count()
//         ],
//         'note' => 'المستخدم اضطر لانتظار كل هذا الوقت قبل رؤية هذه الرسالة!'
//     ]);
// }

// }