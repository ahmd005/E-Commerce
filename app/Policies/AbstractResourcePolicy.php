<?php

namespace App\Policies;

use App\Models\User;

abstract class AbstractResourcePolicy
{
    public function before(User $user, string $ability)
    {
        return $user->hasRole('admin') ? true : null;
    }

    abstract protected function managePermission(): string;

    public function viewAny(User $user): bool
    {
        return $this->canManage($user);
    }

    public function view(User $user, $model): bool
    {
        return $this->canManage($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, $model): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, $model): bool
    {
        return $this->canManage($user);
    }

    public function restore(User $user, $model): bool
    {
        return $this->canManage($user);
    }

    public function forceDelete(User $user, $model): bool
    {
        return $this->canManage($user);
    }

    protected function canManage(User $user): bool
    {
        return $user->hasPermission($this->managePermission());
    }
}
