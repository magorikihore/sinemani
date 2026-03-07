<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Drama permissions
            'manage dramas',
            'publish dramas',
            'delete dramas',

            // Episode permissions
            'manage episodes',
            'upload videos',
            'delete episodes',

            // User permissions
            'manage users',
            'ban users',
            'grant coins',

            // Content permissions
            'manage categories',
            'manage tags',
            'manage banners',
            'manage coin-packages',
            'manage settings',
            'manage reports',

            // Analytics
            'view dashboard',
            'view analytics',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create Admin role with all permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions($permissions);

        // Create Editor role (content management only)
        $editorRole = Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);
        $editorRole->syncPermissions([
            'manage dramas',
            'publish dramas',
            'manage episodes',
            'upload videos',
            'manage categories',
            'manage tags',
            'manage banners',
            'view dashboard',
        ]);

        // Create Moderator role
        $moderatorRole = Role::firstOrCreate(['name' => 'moderator', 'guard_name' => 'web']);
        $moderatorRole->syncPermissions([
            'manage reports',
            'ban users',
            'view dashboard',
        ]);

        // Create basic User role
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    }
}
