<?php

namespace App\Providers;

use App\Models\Permission;
use Illuminate\Support\Facades\Queue;
use App\Models\Role;
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\PermissionRepository;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;
use App\Repositories\CartRepository;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobFailed;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(CartRepositoryInterface::class, CartRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public static $jobStartTime;
    public function boot(): void
    {
        
        Queue::before(function (JobProcessing $event) {

        self::$jobStartTime = microtime(true);

        if ($event->job->resolveName() === \App\Jobs\QueuedUserExportJob::class) {
            Log::channel('queue_jobs')->info('🚀 بدء تنفيذ Job التصدير', [
                'job' => $event->job->resolveName(),
                'queue' => $event->job->getQueue(),
                'memory_start' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB'
            ]);
    }
    });

    // AOP: تسجيل نجاح التنفيذ[cite: 2]
    Queue::after(function (JobProcessed $event) {

      $duration = round(microtime(true) - self::$jobStartTime, 4);
        
        $memoryPeak = round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB';

        Log::channel('queue_jobs')->info('✅ اكتمل تنفيذ Job بنجاح', [
            'job' => $event->job->resolveName(),
            'duration_seconds' => $duration,
            'memory_peak' => $memoryPeak,
        ]);   
         });

    // AOP: معالجة الأخطاء بشكل مركزي[cite: 2, 4]
    Queue::failing(function (JobFailed $event) {
        Log::channel('queue_errors')->error('🔥 فشل في تنفيذ Job: ' . $event->job->resolveName(), [
            'error' => $event->exception->getMessage()
        ]);
    });
        
    }
}
