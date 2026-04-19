<?php

namespace App\Repositories\Contracts;

use App\Models\Role;

interface RoleRepositoryInterface
{
    public function findByUuid(string $uuid): ?Role;

    public function findByName(string $name): ?Role;

    public function create(array $data): Role;
}
