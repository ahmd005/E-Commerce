<?php

namespace App\Jobs;

use App\Services\ExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
class QueuedUserExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    /**
     * عدد مرات إعادة المحاولة عند حدوث فشل.
     */
    public $tries = 3;

    /**
     * عدد الثواني التي يجب انتظرها قبل إعادة المحاولة.
     * يمنح السيرفر وقتاً للتعافي من ضغط الذاكرة أو قاعدة البيانات.
     */
    public $backoff = 60;

    /**
     * تحديد وقت انتهاء المهمة بالكامل (Timeout) 
     * ضروري جداً عند التعامل مع 10,000 مستخدم في مهمة واحدة.
     */
    public $timeout = 600; // 10 دقائق

    // App\Jobs\QueuedUserExportJob.php

protected $exportId;
protected $userId; // الآدمين الذي طلب التصدير
protected $targetUserId; // المستخدم المراد تصديره الآن

public function __construct($exportId, $userId, $targetUserId)
{
    $this->exportId = $exportId;
    $this->userId = $userId;
    $this->targetUserId = $targetUserId;
}

public function handle(ExportService $service)
{
    try {
        // معالجة مستخدم واحد فقط في هذا الجوب
        $service->handleSingleUserExport($this->exportId, $this->userId, $this->targetUserId);
    } catch (\Exception $e) {
        Log::error("فشل تصدير المستخدم {$this->targetUserId}: " . $e->getMessage());
        throw $e;
    }
}

}