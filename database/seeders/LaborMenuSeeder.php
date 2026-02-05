<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LaborMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates Labor Procurement menu with submenus and all permissions.
     */
    public function run(): void
    {
        $this->command->info('Creating Labor Procurement menu structure...');

        // Get max IDs
        $maxMenuId = DB::table('menus')->max('id') ?? 0;
        $maxPermissionId = DB::table('permissions')->max('id') ?? 0;

        // Get role IDs
        $mdRole = DB::table('roles')->where('name', 'Managing Director')->first();
        $adminRole = DB::table('roles')->where('name', 'System Administrator')->first();
        $supervisorRole = DB::table('roles')->where('name', 'Site Supervisor')->first();
        $financeRole = DB::table('roles')->where('name', 'Finance')->first();

        if (!$mdRole && !$adminRole) {
            $this->command->error('No MD or Admin roles found. Please create roles first.');
            return;
        }

        // Create parent menu: Labor Procurement
        $parentMenuId = $maxMenuId + 1;

        $existingParent = DB::table('menus')->where('name', 'Labor Procurement')->first();
        if (!$existingParent) {
            DB::table('menus')->insert([
                'id' => $parentMenuId,
                'name' => 'Labor Procurement',
                'route' => '',
                'icon' => 'fa fa-hard-hat',
                'parent_id' => null,
                'list_order' => 7,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info("Created parent menu: Labor Procurement (ID: {$parentMenuId})");
        } else {
            $parentMenuId = $existingParent->id;
            $this->command->info("Parent menu exists: Labor Procurement (ID: {$parentMenuId})");
        }

        // Define child menus with their permissions
        $childMenus = [
            [
                'name' => 'Labor Dashboard',
                'route' => 'labor.dashboard',
                'icon' => 'fa fa-tachometer-alt',
                'list_order' => 1,
                'crud' => false,
            ],
            [
                'name' => 'Labor Requests',
                'singular' => 'Labor Request',
                'route' => 'labor.requests.index',
                'icon' => 'fa fa-clipboard-list',
                'list_order' => 2,
                'crud' => true,
                'approval' => ['Approve Labor Request', 'Reduce Labor Request', 'Reject Labor Request'],
            ],
            [
                'name' => 'Labor Contracts',
                'singular' => 'Labor Contract',
                'route' => 'labor.contracts.index',
                'icon' => 'fa fa-file-contract',
                'list_order' => 3,
                'crud' => ['Add', 'Edit'],
                'extra' => ['Sign Labor Contract'],
            ],
            [
                'name' => 'Work Logs',
                'singular' => 'Work Log',
                'route' => 'labor.logs.index',
                'icon' => 'fa fa-clipboard-check',
                'list_order' => 4,
                'crud' => ['Add', 'Edit'],
            ],
            [
                'name' => 'Labor Inspections',
                'singular' => 'Labor Inspection',
                'route' => 'labor.inspections.index',
                'icon' => 'fa fa-search-plus',
                'list_order' => 5,
                'crud' => ['Add', 'Edit'],
                'approval' => ['Verify Labor Inspection', 'Approve Labor Inspection', 'Reject Labor Inspection'],
            ],
            [
                'name' => 'Labor Payments',
                'singular' => 'Labor Payment',
                'route' => 'labor.payments.index',
                'icon' => 'fa fa-money-bill-wave',
                'list_order' => 6,
                'crud' => false,
                'extra' => ['Approve Labor Payment', 'Process Labor Payment'],
            ],
        ];

        $menuId = $parentMenuId;
        $permissionId = $maxPermissionId;

        // Create parent menu permission
        $this->createPermission('Labor Procurement', $permissionId, [$mdRole?->id, $adminRole?->id, $supervisorRole?->id]);

        foreach ($childMenus as $menu) {
            $menuId++;

            // Insert menu if not exists
            $existingMenu = DB::table('menus')->where('name', $menu['name'])->first();
            if (!$existingMenu) {
                DB::table('menus')->insert([
                    'id' => $menuId,
                    'name' => $menu['name'],
                    'route' => $menu['route'],
                    'icon' => $menu['icon'],
                    'parent_id' => $parentMenuId,
                    'list_order' => $menu['list_order'],
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Menu view permission (assigned to MD, Admin, Supervisor, and Finance for payments)
            $viewRoles = array_filter([$mdRole?->id, $adminRole?->id, $supervisorRole?->id]);
            if ($menu['name'] === 'Labor Payments') {
                $viewRoles[] = $financeRole?->id;
                $viewRoles = array_filter($viewRoles);
            }
            $this->createPermission($menu['name'], $permissionId, $viewRoles);
            $this->command->info("  ├─ {$menu['name']}");

            // CRUD permissions
            if ($menu['crud'] ?? false) {
                $singular = $menu['singular'] ?? rtrim($menu['name'], 's');
                $actions = is_array($menu['crud']) ? $menu['crud'] : ['Add', 'Edit', 'Delete'];

                foreach ($actions as $action) {
                    $permName = "$action $singular";
                    $crudRoles = [$mdRole?->id, $adminRole?->id];
                    // Supervisor can add work logs and inspections
                    if (in_array($action, ['Add', 'Edit']) && in_array($menu['name'], ['Work Logs', 'Labor Inspections'])) {
                        $crudRoles[] = $supervisorRole?->id;
                    }
                    $this->createPermission($permName, $permissionId, array_filter($crudRoles));
                    $this->command->info("      └─ $permName");
                }
            }

            // Extra permissions (non-CRUD actions)
            if (!empty($menu['extra'])) {
                foreach ($menu['extra'] as $extraPerm) {
                    $roles = [$mdRole?->id, $adminRole?->id];
                    if (str_contains($extraPerm, 'Process')) {
                        // Finance can process payments
                        $roles[] = $financeRole?->id;
                    }
                    $this->createPermission($extraPerm, $permissionId, array_filter($roles));
                    $this->command->info("      └─ $extraPerm");
                }
            }

            // Approval permissions
            if (!empty($menu['approval'])) {
                foreach ($menu['approval'] as $approvalPerm) {
                    // Verify goes to Supervisor + MD, Approve/Reject goes to MD only
                    if (str_contains($approvalPerm, 'Verify')) {
                        $roles = array_filter([$supervisorRole?->id, $mdRole?->id, $adminRole?->id]);
                    } else {
                        $roles = array_filter([$mdRole?->id, $adminRole?->id]);
                    }
                    $this->createPermission($approvalPerm, $permissionId, $roles);
                    $this->command->info("      └─ $approvalPerm");
                }
            }
        }

        $this->command->info("\nLabor Procurement menu and permissions created successfully!");
    }

    /**
     * Create permission if not exists and assign to roles
     */
    private function createPermission(string $name, int &$permissionId, array $roleIds): void
    {
        $existing = DB::table('permissions')->where('name', $name)->first();

        if ($existing) {
            $permId = $existing->id;
        } else {
            $permissionId++;
            $permId = $permissionId;

            DB::table('permissions')->insert([
                'id' => $permId,
                'name' => $name,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Assign to roles
        foreach (array_filter($roleIds) as $roleId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permId,
                'role_id' => $roleId,
            ]);
        }
    }
}
