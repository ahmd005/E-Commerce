<?php

namespace App\Jobs;

use App\Services\ExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
class QueuedUserExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $tries = 3;

    public $backoff = 60;

    public $timeout = 600; 


protected $exportId;
protected $userId; 
protected $targetUserId; 
public function __construct($exportId, $userId, $targetUserId)
{
    $this->exportId = $exportId;
    $this->userId = $userId;
    $this->targetUserId = $targetUserId;
}

public function handle(ExportService $service)
{
    try {
        $service->handleSingleUserExport($this->exportId, $this->userId, $this->targetUserId);
    } catch (\Exception $e) {
        Log::error("فشل تصدير المستخدم {$this->targetUserId}: " . $e->getMessage());
        throw $e;
    }
}

}