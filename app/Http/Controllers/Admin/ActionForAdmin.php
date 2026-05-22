<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\QueuedUserExportJob;
use App\Services\ExportService;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

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
        $targetUserIds = \App\Models\User::pluck('id'); 
        $totalCount = $targetUserIds->count();

        $this->exportService->updateStatus($exportId, 'processing', $userId, null, $totalCount); 
        
        
        foreach ($targetUserIds as $targetId) {
            $this->exportService->handleSingleUserExport($exportId, $userId, $targetId);
        }
        
        $path = 'exports/export_' . $exportId . '.csv'; 
        
        $this->exportService->updateStatus($exportId, 'completed', $userId, $path, $totalCount);

        return response()->json([
            'success' => true,
            'message' => 'تم التصدير المتزامن بنجاح',
            'download_url' => url('/storage/' . $path),
            'records_processed' => $totalCount
        ]);
    } catch (\Exception $e) {
        $this->exportService->updateStatus($exportId, 'failed', $userId, null, 0, $e->getMessage());
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
}



// public function exportWithQueue(Request $request)
// {
//     if (!auth()->check()) {
//         return response()->json(['error' => 'Unauthenticated'], 401);
//     }

//     $exportId = uniqid('batch_', true);
//     $userId = auth()->id(); 

//     $targetUserIds = \App\Models\User::pluck('id'); 

//     $this->exportService->updateStatus($exportId, 'pending', $userId, null, $targetUserIds->count()); 

//     foreach ($targetUserIds as $targetId) {
//         \App\Jobs\QueuedUserExportJob::dispatch($exportId, $userId, $targetId)
//             ->onQueue('exports');
//     }

//     return response()->json([
//         'success' => true, 
//         'export_id' => $exportId, 
//         'jobs_count' => $targetUserIds->count()
//     ]);
// }
public function exportWithQueue(Request $request)
{
    if (!auth()->check()) {
        return response()->json(['error' => 'Unauthenticated'], 401);
    }

    $exportId = uniqid('batch_', true);
    $userId = auth()->id(); 

    // استخدام الـ Repository لجلب العدد الإجمالي فقط (استعلام سريع جداً وخفيف)
    // نستخدم الـ Builder المتاح في UserRepository الذي قمت بإنشائه
    $userRepository = app(\App\Repositories\Contracts\UserRepositoryInterface::class);
    $totalCount = $userRepository->getExportQuery()->count();

    // تحديث حالة التصدير إلى معلق
    $this->exportService->updateStatus($exportId, 'pending', $userId, null, $totalCount); 

    // 🔥 الحل السحري: إرسال وظيفة واحدة رئيسية فقط للطابور وينتهي دور الـ Controller فوراً!
    \App\Jobs\InitiateUserExportJob::dispatch($exportId, $userId)->onQueue('exports');

    return response()->json([
        'success' => true, 
        'export_id' => $exportId, 
        'message' => 'تم بدء عملية التصدير في الخلفية بنجاح.',
        'total_records' => $totalCount
    ]);
}
}