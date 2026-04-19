<?php

namespace App\Traits;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasRolesAndPermissions
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_user');
    }

    public function getAllPermissions(): Collection
    {
        return $this->permissions
            ->merge($this->roles->flatMap->permissions)
            ->unique('id');
    }

    public function assignRole(...$roles): static
    {
        $this->roles()->syncWithoutDetaching($this->resolveRoleIds($roles));

        return $this;
    }

    public function removeRole(...$roles): static
    {
        $this->roles()->detach($this->resolveRoleIds($roles));

        return $this;
    }

    public function hasRole(string $roleName): bool
    {
        return $this->roles->contains(fn (Role $role) => $role->name === $roleName);
    }

    public function givePermissionTo(...$permissions): static
    {
        $this->permissions()->syncWithoutDetaching($this->resolvePermissionIds($permissions));

        return $this;
    }

    public function revokePermissionTo(...$permissions): static
    {
        $this->permissions()->detach($this->resolvePermissionIds($permissions));

        return $this;
    }

    public function hasPermission(string $permissionName): bool
    {
        return $this->getAllPermissions()->contains(fn (Permission $permission) => $permission->name === $permissionName);
    }

    protected function resolveRoleIds(array $roles): array
    {
        return collect($roles)
            ->flatten()
            ->map(fn ($role) => $this->normalizeRoleIdentifier($role))
            ->filter()
            ->unique()
            ->all();
    }

    protected function resolvePermissionIds(array $permissions): array
    {
        return collect($permissions)
            ->flatten()
            ->map(fn ($permission) => $this->normalizePermissionIdentifier($permission))
            ->filter()
            ->unique()
            ->all();
    }

    protected function normalizeRoleIdentifier($role): ?int
    {
        if ($role instanceof Role) {
            return $role->id;
        }

        if (is_int($role) || ctype_digit((string) $role)) {
            return (int) $role;
        }

        return Role::where('name', $role)->orWhere('uuid', $role)->value('id');
    }

    protected function normalizePermissionIdentifier($permission): ?int
    {
        if ($permission instanceof Permission) {
            return $permission->id;
        }

        if (is_int($permission) || ctype_digit((string) $permission)) {
            return (int) $permission;
        }

        return Permission::where('name', $permission)->orWhere('uuid', $permission)->value('id');
    }
}
