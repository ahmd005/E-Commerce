<?php

// filepath: app/Repositories/UserRepository.php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class UserRepository implements UserRepositoryInterface
{
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function making(array $data): User
    {
        return User::create($data);
    }

    public function GetUserWithConditions(array $conditions): ?User
    {
        $query = User::query();
        
        foreach ($conditions as $field => $value) {
            $query->where($field, $value);
        }
        
        return $query->first();
    }

    /**
     * الحصول على Query للتصدير
     */
    public function getExportQuery(): Builder
    {
        return User::query()->select('id', 'name', 'email', 'created_at', 'updated_at');
    }
}
