<?php

namespace App\Policies;

class PermissionPolicy extends AbstractResourcePolicy
{
    protected function managePermission(): string
    {
        return 'manage permissions';
    }
}
