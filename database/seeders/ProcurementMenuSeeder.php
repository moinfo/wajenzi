<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProcurementMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates Procurement menu with submenus and all permissions (CRUD + Approval).
     */
    public function run(): void
    {
        $this->command->info('Creating Procurement menu structure...');

        // Get max IDs
        $maxMenuId = DB::table('menus')->max('id') ?? 0;
        $maxPermissionId = DB::table('permissions')->max('id') ?? 0;

        // Get role IDs
        $mdRole = DB::table('roles')->where('name', 'Managing Director')->first();
        $adminRole = DB::table('roles')->where('name', 'System Administrator')->first();
        $supervisorRole = DB::table('roles')->where('name', 'Site Supervisor')->first();

        if (!$mdRole && !$adminRole) {
            $this->command->error('No MD or Admin roles found. Please create roles first.');
            return;
        }

        // Create parent menu: Procurement
        $parentMenuId = $maxMenuId + 1;

        $existingParent = DB::table('menus')->where('name', 'Procurement')->first();
        if (!$existingParent) {
            DB::table('menus')->insert([
                'id' => $parentMenuId,
                'name' => 'Procurement',
                'route' => '',
                'icon' => 'fa fa-shopping-cart',
                'parent_id' => null,
                'list_order' => 6,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info("Created parent menu: Procurement (ID: {$parentMenuId})");
        } else {
            $parentMenuId = $existingParent->id;
            $this->command->info("Parent menu exists: Procurement (ID: {$parentMenuId})");
        }

        // Define child menus with their CRUD permissions
        $childMenus = [
            [
                'name' => 'Procurement Dashboard',
                'route' => 'procurement_dashboard',
                'icon' => 'fa fa-tachometer-alt',
                'list_order' => 1,
                'crud' => false, // Dashboard has no CRUD
            ],
            [
                'name' => 'Material Requests',
                'singular' => 'Material Request',
                'route' => 'project_material_requests',
                'icon' => 'fa fa-clipboard-list',
                'list_order' => 2,
                'crud' => true,
                'approval' => ['Approve Material Request'],
            ],
            [
                'name' => 'Supplier Quotations',
                'singular' => 'Supplier Quotation',
                'route' => 'supplier_quotations',
                'icon' => 'fa fa-file-invoice-dollar',
                'list_order' => 3,
                'crud' => true,
            ],
            [
                'name' => 'Quotation Comparisons',
                'singular' => 'Quotation Comparison',
                'route' => 'quotation_comparisons',
                'icon' => 'fa fa-balance-scale-left',
                'list_order' => 4,
                'crud' => true,
                'approval' => ['Approve Quotation Comparison'],
            ],
            [
                'name' => 'Material Inspections',
                'singular' => 'Material Inspection',
                'route' => 'material_inspections',
                'icon' => 'fa fa-search-plus',
                'list_order' => 5,
                'crud' => true,
                'approval' => ['Verify Material Inspection', 'Approve Material Inspection'],
            ],
        ];

        $menuId = $parentMenuId;
        $permissionId = $maxPermissionId;

        // Create parent menu permission
        $this->createPermission('Procurement', $permissionId, [$mdRole?->id, $adminRole?->id]);

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

            // Menu view permission (assigned to MD, Admin, and Supervisor)
            $viewRoles = array_filter([$mdRole?->id, $adminRole?->id, $supervisorRole?->id]);
            $this->createPermission($menu['name'], $permissionId, $viewRoles);
            $this->command->info("  ├─ {$menu['name']}");

            // CRUD permissions (Add, Edit, Delete)
            if ($menu['crud'] ?? false) {
                $singular = $menu['singular'] ?? rtrim($menu['name'], 's');
                foreach (['Add', 'Edit', 'Delete'] as $action) {
                    $permName = "$action $singular";
                    $this->createPermission($permName, $permissionId, [$mdRole?->id, $adminRole?->id]);
                    $this->command->info("      └─ $permName");
                }
            }

            // Approval permissions
            if (!empty($menu['approval'])) {
                foreach ($menu['approval'] as $approvalPerm) {
                    // Verify goes to Supervisor + MD, Approve goes to MD only
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

        $this->command->info("\nProcurement menu and permissions created successfully!");
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
