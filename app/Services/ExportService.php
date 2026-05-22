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

    DB::table('exports')
        ->where('export_id', $exportId)
        ->increment('records_count');
}



public function updateStatus($exportId, $status, $userId, $filePath = null, $count = 0, $error = null)
{
    DB::table('exports')->updateOrInsert(
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