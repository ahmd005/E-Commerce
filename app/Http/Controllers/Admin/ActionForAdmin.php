<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\QueuedUserExportJob;
use App\Services\ExportService;
use Illuminate\Http\Request;

class ActionForAdmin extends Controller
{
    protected $exportService;

    public function __construct(ExportService $exportService)
    {
        $this->exportService = $exportService;
    }

   public function exportSync(Request $request)
{
    $exportId = uniqid('exp_sync_', true);
    $userId = auth()->id(); 
    
    try {
        $this->exportService->updateStatus($exportId, 'processing', $userId); 
        
        $path = $this->exportService->handleExport($exportId, $userId);
        
        return response()->json([
            'success' => true,
            'message' => 'تم التصدير بنجاح',
            'download_url' => url('/storage/' . $path)
        ]);
    } catch (\Exception $e) {
        $this->exportService->updateStatus($exportId, 'failed', $userId, null, 0, $e->getMessage());
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

//  public function exportWithQueue(Request $request)
// {
//     if (!auth()->check()) {
//         return response()->json(['error' => 'Unauthenticated'], 401);
//     }
//     $exportId = uniqid('exp_queue_', true);
//     $userId = auth()->id(); 

//     $this->exportService->updateStatus($exportId, 'pending', $userId); 

//     QueuedUserExportJob::dispatch($exportId, $userId)
//         ->onQueue('exports');

//     return response()->json(['success' => true, 'export_id' => $exportId]);
// }
// App\Http\Controllers\Admin\ActionForAdmin.php

public function exportWithQueue(Request $request)
{
    if (!auth()->check()) {
        return response()->json(['error' => 'Unauthenticated'], 401);
    }

    $exportId = uniqid('batch_', true);
    $userId = auth()->id(); 

    // جلب كل المعرفات التي نريد تصديرها
    $targetUserIds = \App\Models\User::pluck('id'); 

    $this->exportService->updateStatus($exportId, 'pending', $userId, null, $targetUserIds->count()); 

    // إرسال جوب منفصل لكل مستخدم
    foreach ($targetUserIds as $targetId) {
        \App\Jobs\QueuedUserExportJob::dispatch($exportId, $userId, $targetId)
            ->onQueue('exports');
    }

    return response()->json([
        'success' => true, 
        'export_id' => $exportId, 
        'jobs_count' => $targetUserIds->count()
    ]);
}
}