<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SiteStockMenuSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Adding Site Free-Stock menu items to Procurement...');

        $mdRole         = DB::table('roles')->where('name', 'Managing Director')->first();
        $adminRole      = DB::table('roles')->where('name', 'System Administrator')->first();
        $supervisorRole = DB::table('roles')->where('name', 'Site Supervisor')->first();

        // Find Procurement parent
        $procurementMenu = DB::table('menus')->where('name', 'Procurement')->first();
        if (!$procurementMenu) {
            $this->command->error('Procurement parent menu not found. Run ProcurementMenuSeeder first.');
            return;
        }

        $parentMenuId = $procurementMenu->id;

        $childMenus = [
            [
                'name'       => 'Site Free-Stock',
                'singular'   => 'Site Stock Item',
                'route'      => 'project_stock.index',
                'icon'       => 'fa fa-boxes',
                'list_order' => 7,
                'crud'       => true,
                'roles'      => array_filter([$mdRole?->id, $adminRole?->id, $supervisorRole?->id]),
                'crud_roles' => array_filter([$mdRole?->id, $adminRole?->id, $supervisorRole?->id]),
            ],
            [
                'name'       => 'Stock Receipts',
                'singular'   => 'Stock Receipt',
                'route'      => 'project_stock_receipts.index',
                'icon'       => 'fa fa-inbox',
                'list_order' => 8,
                'crud'       => true,
                'roles'      => array_filter([$mdRole?->id, $adminRole?->id, $supervisorRole?->id]),
                'crud_roles' => array_filter([$mdRole?->id, $adminRole?->id, $supervisorRole?->id]),
            ],
        ];

        foreach ($childMenus as $menu) {
            // Insert menu row if missing
            $existing = DB::table('menus')->where('name', $menu['name'])->first();
            if (!$existing) {
                DB::table('menus')->insert([
                    'name'       => $menu['name'],
                    'route'      => $menu['route'],
                    'icon'       => $menu['icon'],
                    'parent_id'  => $parentMenuId,
                    'list_order' => $menu['list_order'],
                    'status'     => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->command->info("  ├─ Created menu: {$menu['name']}");
            } else {
                $this->command->info("  ├─ Menu already exists: {$menu['name']}");
            }

            // View permission
            $this->createPermission($menu['name'], $menu['roles']);
            $this->command->info("      └─ Permission: {$menu['name']}");

            // CRUD permissions
            if ($menu['crud']) {
                foreach (['Add', 'Edit', 'Delete'] as $action) {
                    $permName = "$action {$menu['singular']}";
                    $this->createPermission($permName, $menu['crud_roles']);
                    $this->command->info("      └─ Permission: $permName");
                }
            }
        }

        $this->command->info("\nSite Free-Stock menu and permissions created successfully!");
    }

    private function createPermission(string $name, array $roleIds): void
    {
        $existing = DB::table('permissions')->where('name', $name)->first();

        if ($existing) {
            $permId = $existing->id;
        } else {
            $permId = DB::table('permissions')->insertGetId([
                'name'       => $name,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (array_filter($roleIds) as $roleId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permId,
                'role_id'       => $roleId,
            ]);
        }
    }
}
