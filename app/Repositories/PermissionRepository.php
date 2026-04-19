<?php

namespace App\Repositories;

use App\Models\Permission;
use App\Repositories\Contracts\PermissionRepositoryInterface;

class PermissionRepository implements PermissionRepositoryInterface
{
    public function findByUuid(string $uuid): ?Permission
    {
        return Permission::where('uuid', $uuid)->first();
    }

    public function findByName(string $name): ?Permission
    {
        return Permission::where('name', $name)->first();
    }

    public function create(array $data): Permission
    {
        return Permission::create($data);
    }
}
