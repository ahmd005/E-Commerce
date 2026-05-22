<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Illuminate\Support\Facades\Log;

class ProcessInventoryReport implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
   public function handle() {
    Log::info("=== بدء المعالجة الاحترافية من RabbitMQ (Batch Mode) ===");

    // تسجيل الذاكرة قبل البدء تماماً
    $initialMemory = round(memory_get_usage() / 1024 / 1024, 2);
    Log::info("الذاكرة عند نقطة البداية: {$initialMemory} MB");

    // تقسيم السجلات إلى دفعات (Chunks)
    \App\Models\Order::where('status', 'completed')->chunk(1000, function ($orders, $page) {
        
        foreach ($orders as $order) {
            // محاكاة المعالجة
            usleep(5000); 
        }

        // حساب الذاكرة بعد كل دفعة (Batch)
        $currentMemory = round(memory_get_usage() / 1024 / 1024, 2);
        
        // طباعة رقم الدفعة واستهلاك الذاكرة الحالي
        Log::info("تم إنهاء الدفعة رقم ($page). استهلاك الذاكرة الحالي: {$currentMemory} MB");
    });

    Log::info("=== اكتمل التقرير بالكامل والذاكرة بقيت مستقرة ===");
}
}
