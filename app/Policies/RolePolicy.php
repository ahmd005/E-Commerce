<?php

namespace App\Policies;

class RolePolicy extends AbstractResourcePolicy
{
    protected function managePermission(): string
    {
        return 'manage roles';
    }
}
