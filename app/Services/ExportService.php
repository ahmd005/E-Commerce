<?php

namespace App\Services;

use App\Exports\UsersExport;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExportService
{

public function handleSingleUserExport($exportId, $adminId, $targetUserId)
{
    $user = \App\Models\User::find($targetUserId);
    if (!$user) return;

    $fileName = 'export_' . $exportId . '.csv';
    $filePath = storage_path('app/public/exports/' . $fileName);

    $file = fopen($filePath, 'a');
    fputcsv($file, [
        $user->id, 
        $user->name, 
        $user->email, 
        now()->toDateTimeString()
    ]);
    fclose($file);

    \DB::table('exports')
        ->where('export_id', $exportId)
        ->increment('records_count');
}

// public function handleExport($exportId, $userId)
// {
//     $fileName = 'users_export_' . $exportId . '_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
//     $filePath = 'exports/' . $fileName;

//     Excel::store(app(UsersExport::class, ['exportId' => $exportId, 'userId' => $userId]), $filePath, 'public');

//     $userCount = User::count(); 
    
//     $this->updateStatus($exportId, 'completed', $userId, $filePath, $userCount);

//     return $filePath;
// }



public function handleExport($exportId, $userId)
{
    // 1. تحديد اسم ومسار الملف
    $fileName = 'exports/sync_export_' . $exportId . '.csv';
    $fullPath = storage_path('app/public/' . $fileName);

    // التأكد من وجود المجلد
    if (!file_exists(storage_path('app/public/exports'))) {
        mkdir(storage_path('app/public/exports'), 0755, true);
    }

    // 2. جلب البيانات وكتابتها في ملف CSV (أسرع من Excel في الـ Sync)
    $users = \App\Models\User::all();
    $file = fopen($fullPath, 'w');
    
    // إضافة العناوين
    fputcsv($file, ['ID', 'Name', 'Email', 'Created At']);

    foreach ($users as $user) {
        fputcsv($file, [$user->id, $user->name, $user->email, $user->created_at]);
    }
    fclose($file);

    // 3. تحديث حالة الجدول إلى مكتمل
    $this->updateStatus($exportId, 'completed', $userId, $fileName, $users->count());

    return $fileName;
}







public function updateStatus($exportId, $status, $userId, $filePath = null, $count = 0, $error = null)
{
    \DB::table('exports')->updateOrInsert(
        ['export_id' => $exportId],
        [
            'user_id'       => $userId,       
            'status'        => $status,
            'file_path'     => $filePath,
            'records_count' => $count,
            'error_message' => $error,
            'updated_at'    => now(),
            'completed_at'  => $status === 'completed' ? now() : null,
        ]
    );
}
}