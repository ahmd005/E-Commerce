<?php

// filepath: app/Exports/UsersExport.php

namespace App\Exports;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

class UsersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $exportId;
    protected $userId;
    protected UserRepositoryInterface $userRepository;

    public function __construct($exportId, $userId, UserRepositoryInterface $userRepository)
    {
        $this->exportId = $exportId;
        $this->userId = $userId;
        $this->userRepository = $userRepository;
    }
    
    /**
     * استخدام UserRepository في Query لتحسين الأداء
     */
    public function query()
    {
        // استخدام UserRepository للحصول على الـ Query
        return $this->userRepository->getExportQuery();
    }
    
    /**
     * تنسيق البيانات لكل صف
     */
    public function map($user): array
    {
        // تسجيل تقدم التصدير (كل 100 مستخدم)
        static $counter = 0;
        $counter++;
        
        if ($counter % 100 === 0) {
            \Log::channel('export')->info('🔄 تقدم التصدير', [
                'export_id' => $this->exportId,
                'processed_records' => $counter
            ]);
        }
        
        return [
            $user->id,
            $user->name,
            $user->email,
            $user->created_at->format('Y-m-d H:i:s'),
            $user->updated_at->format('Y-m-d H:i:s'),
        ];
    }
    
    /**
     * عناوين الأعمدة
     */
    public function headings(): array
    {
        return [
            '#',
            'الاسم الكامل',
            'البريد الإلكتروني',
            'تاريخ الإنشاء',
            'آخر تحديث'
        ];
    }
    
  
}