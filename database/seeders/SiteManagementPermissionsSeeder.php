<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SiteManagementPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Site Management Permissions with permission_type
        $permissions = [
            // Main Menu Access
            ['name' => 'Site Management', 'permission_type' => 'MENU'],
            ['name' => 'Site Daily Reports', 'permission_type' => 'MENU'],
            
            // Sub Menu Access
            ['name' => 'Sites', 'permission_type' => 'MENU'],
            ['name' => 'Supervisor Assignments', 'permission_type' => 'MENU'],
            ['name' => 'Site Reports List', 'permission_type' => 'MENU'],
            ['name' => 'My Site Reports', 'permission_type' => 'MENU'],
            
            // Sites CRUD
            ['name' => 'View Sites', 'permission_type' => 'CRUD'],
            ['name' => 'Add Sites', 'permission_type' => 'CRUD'],
            ['name' => 'Edit Sites', 'permission_type' => 'CRUD'],
            ['name' => 'Delete Sites', 'permission_type' => 'CRUD'],
            ['name' => 'Export Sites', 'permission_type' => 'CRUD'],
            
            // Supervisor Assignments CRUD
            ['name' => 'View Site Assignments', 'permission_type' => 'CRUD'],
            ['name' => 'Add Site Assignments', 'permission_type' => 'CRUD'],
            ['name' => 'Edit Site Assignments', 'permission_type' => 'CRUD'],
            ['name' => 'Delete Site Assignments', 'permission_type' => 'CRUD'],
            ['name' => 'View Assignment History', 'permission_type' => 'CRUD'],
            
            // Site Daily Reports CRUD
            ['name' => 'View All Site Reports', 'permission_type' => 'CRUD'],
            ['name' => 'View Own Site Reports', 'permission_type' => 'CRUD'],
            ['name' => 'Add Site Reports', 'permission_type' => 'CRUD'],
            ['name' => 'Edit Own Site Reports', 'permission_type' => 'CRUD'],
            ['name' => 'Edit All Site Reports', 'permission_type' => 'CRUD'],
            ['name' => 'Delete Own Site Reports', 'permission_type' => 'CRUD'],
            ['name' => 'Delete All Site Reports', 'permission_type' => 'CRUD'],
            
            // Site Report Actions
            ['name' => 'Submit Site Reports', 'permission_type' => 'CRUD'],
            ['name' => 'Approve Site Reports', 'permission_type' => 'CRUD'],
            ['name' => 'Reject Site Reports', 'permission_type' => 'CRUD'],
            ['name' => 'Export Site Reports', 'permission_type' => 'CRUD'],
            ['name' => 'Share Site Reports', 'permission_type' => 'CRUD'],
            
            // Site Dashboard & Reports
            ['name' => 'Site Dashboard', 'permission_type' => 'REPORT'],
            ['name' => 'Site Analytics Report', 'permission_type' => 'REPORT'],
            ['name' => 'Site Progress Report', 'permission_type' => 'REPORT'],
            ['name' => 'Supervisor Performance Report', 'permission_type' => 'REPORT'],
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
                    'updated_at' => now()
                ]
            );
        }

        // Assign permissions to existing roles
        $this->assignPermissionsToRoles($permissions);

        $this->command->info('Site Management permissions seeded successfully!');
    }

    private function assignPermissionsToRoles($permissions)
    {
        // Extract permission names
        $permissionNames = array_column($permissions, 'name');

        // Super Admin gets all permissions
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissionNames);
        }

        // Admin gets all permissions
        $admin = Role::where('name', 'Admin')->first();
        if ($admin) {
            $admin->givePermissionTo($permissionNames);
        }

        // Create or update Project Manager role
        $projectManager = Role::firstOrCreate(['name' => 'Project Manager']);
        $projectManagerPermissions = [
            // Menus
            'Site Management',
            'Site Daily Reports',
            'Sites',
            'Supervisor Assignments',
            'Site Reports List',
            
            // Sites (no delete)
            'View Sites',
            'Add Sites',
            'Edit Sites',
            'Export Sites',
            
            // Assignments
            'View Site Assignments',
            'Add Site Assignments',
            'Edit Site Assignments',
            'View Assignment History',
            
            // Reports
            'View All Site Reports',
            'Edit All Site Reports',
            'Approve Site Reports',
            'Reject Site Reports',
            'Export Site Reports',
            'Share Site Reports',
            
            // Dashboard
            'Site Dashboard',
            'Site Analytics Report',
            'Site Progress Report',
            'Supervisor Performance Report',
        ];
        $projectManager->syncPermissions($projectManagerPermissions);

        // Create Site Supervisor role
        $supervisor = Role::firstOrCreate(['name' => 'Site Supervisor']);
        $supervisorPermissions = [
            // Menus
            'Site Daily Reports',
            'My Site Reports',
            
            // Limited access
            'View Sites',
            'View Site Assignments',
            'View Own Site Reports',
            'Add Site Reports',
            'Edit Own Site Reports',
            'Delete Own Site Reports',
            'Submit Site Reports',
            'Export Site Reports',
            'Share Site Reports',
            
            // Dashboard
            'Site Dashboard',
        ];
        $supervisor->syncPermissions($supervisorPermissions);

        // Regular User permissions
        $user = Role::where('name', 'User')->first();
        if ($user) {
            $userPermissions = [
                'View Sites',
                'View Site Assignments',
                'View All Site Reports',
                'Export Site Reports',
            ];
            $user->givePermissionTo($userPermissions);
        }
    }
}