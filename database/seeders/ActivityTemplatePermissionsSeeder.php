<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ActivityTemplatePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Activity Template Permissions
        $permissions = [
            // Menu Access
            ['name' => 'Activity Templates', 'permission_type' => 'MENU'],

            // CRUD
            ['name' => 'Add Activity Template', 'permission_type' => 'CRUD'],
            ['name' => 'Edit Activity Template', 'permission_type' => 'CRUD'],
            ['name' => 'Delete Activity Template', 'permission_type' => 'CRUD'],
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                [
                    'name' => $permission['name'],
                    'guard_name' => 'web',
                    'permission_type' => $permission['permission_type'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $permissionNames = array_column($permissions, 'name');

        // Assign to roles using direct DB insert to avoid guard mismatch
        $rolesToAssign = ['System Administrator', 'Admin', 'Managing Director'];

        foreach ($rolesToAssign as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if (!$role) continue;

            foreach ($permissionNames as $permName) {
                $permission = Permission::where('name', $permName)->first();
                if (!$permission) continue;

                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permission->id,
                    'role_id' => $role->id,
                ]);
            }
        }

        // Reset cache after assignment
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Activity Template permissions seeded successfully!');
        $this->command->info('Created ' . count($permissions) . ' permissions');
        $this->command->info('Assigned to: ' . implode(', ', $rolesToAssign));
    }
}
