<?php

namespace App\Repositories\Contracts;

use App\Models\Permission;

interface PermissionRepositoryInterface
{
    public function findByUuid(string $uuid): ?Permission;

    public function findByName(string $name): ?Permission;

    public function create(array $data): Permission;
}
