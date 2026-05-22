<?php

namespace App\Jobs;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Jobs\QueuedUserExportJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class InitiateUserExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $exportId;
    protected $userId;

    public function __construct($exportId, $userId)
    {
        $this->exportId = $exportId;
        $this->userId = $userId;
    }

    public function handle(UserRepositoryInterface $userRepository)
    {
        // جلب الـ Query الجاهز من الـ Repository الخاص بك
        // استخدام chunkById يضمن استهلاك ذاكرة يقارب الصفر وسرعة معالجة عالية
        $userRepository->getExportQuery()
            ->chunkById(1000, function ($users) {
                foreach ($users as $user) {
                    // توزيع وظيفتك الأصلية لكل مستخدم دون تغيير في منطقها
                    QueuedUserExportJob::dispatch($this->exportId, $this->userId, $user->id)
                        ->onQueue('exports');
                }
            }, 'id');
    }
}