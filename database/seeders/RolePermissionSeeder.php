<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
        ], [
            'description' => 'Full access to all system resources.',
        ]);

        $userRole = Role::firstOrCreate([
            'name' => 'user',
        ], [
            'description' => 'Default role for normal users.',
        ]);

        $manageProducts = Permission::firstOrCreate([
            'name' => 'manage products',
        ], [
            'description' => 'Create, edit, and delete products.',
        ]);

        $manageOrders = Permission::firstOrCreate([
            'name' => 'manage orders',
        ], [
            'description' => 'View and manage customer orders.',
        ]);

        $manageUsers = Permission::firstOrCreate([
            'name' => 'manage users',
        ], [
            'description' => 'Manage user accounts and roles.',
        ]);

        $manageRoles = Permission::firstOrCreate([
            'name' => 'manage roles',
        ], [
            'description' => 'Create and manage system roles.',
        ]);

        $managePermissions = Permission::firstOrCreate([
            'name' => 'manage permissions',
        ], [
            'description' => 'Create and manage permissions.',
        ]);

        $adminRole->permissions()->syncWithoutDetaching([
            $manageProducts->id,
            $manageOrders->id,
            $manageUsers->id,
            $manageRoles->id,
            $managePermissions->id,
        ]);

        $firstUser = User::first();

        if ($firstUser) {
            $firstUser->assignRole($adminRole);
        }
    }
}
