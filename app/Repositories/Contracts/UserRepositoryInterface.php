<?php

// filepath: app/Repositories/Contracts/UserRepositoryInterface.php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    public function making(array $data): User;
    
    public function GetUserWithConditions(array $conditions): ?User;
    
    /**
     * الحصول على Query للتصدير
     */
    public function getExportQuery(): Builder;
}
