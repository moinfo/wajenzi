<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SitePaylogMenuSeeder extends Seeder
{
    /**
     * Standalone "Site Paylog" top-level menu with its own children.
     * Menus are gated only by status=ACTIVE (see AdminComposer::getUserMenu),
     * so creating the rows is enough to surface them in the sidebar.
     */
    public function run(): void
    {
        $this->command->info('Creating Site Paylog menu...');

        $maxMenuId       = DB::table('menus')->max('id') ?? 0;
        $maxPermissionId = DB::table('permissions')->max('id') ?? 0;

        $mdRole         = DB::table('roles')->where('name', 'Managing Director')->first();
        $adminRole      = DB::table('roles')->where('name', 'System Administrator')->first();
        $supervisorRole = DB::table('roles')->where('name', 'Site Supervisor')->first();

        // Parent menu
        $parentMenuId = $maxMenuId + 1;
        $existingParent = DB::table('menus')->where('name', 'Site Paylog')->first();
        if (!$existingParent) {
            DB::table('menus')->insert([
                'id'         => $parentMenuId,
                'name'       => 'Site Paylog',
                'route'      => '',
                'icon'       => 'fa fa-money-bill-wave',
                'parent_id'  => null,
                'list_order' => 7,
                'status'     => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info("Created parent menu: Site Paylog (ID: {$parentMenuId})");
        } else {
            $parentMenuId = $existingParent->id;
        }

        $children = [
            ['name' => 'Daily Payments',         'route' => 'site_paylog',                'icon' => 'fa fa-cash-register',  'list_order' => 1],
            ['name' => 'Daily Payment Report',   'route' => 'site_paylog.daily_report',   'icon' => 'fa fa-file-invoice',   'list_order' => 2],
            ['name' => 'Monthly Payment Report', 'route' => 'site_paylog.monthly_report', 'icon' => 'fa fa-calendar-alt',   'list_order' => 3],
            ['name' => 'Payment Channels',       'route' => 'site_paylog.channels',       'icon' => 'fa fa-university',     'list_order' => 4],
        ];

        $menuId       = max($parentMenuId, DB::table('menus')->max('id') ?? 0);
        $permissionId = $maxPermissionId;

        // Parent view permission
        $this->createPermission('Site Paylog', $permissionId, array_filter([$mdRole?->id, $adminRole?->id, $supervisorRole?->id]));

        foreach ($children as $menu) {
            if (!DB::table('menus')->where('name', $menu['name'])->exists()) {
                $menuId++;
                DB::table('menus')->insert([
                    'id'         => $menuId,
                    'name'       => $menu['name'],
                    'route'      => $menu['route'],
                    'icon'       => $menu['icon'],
                    'parent_id'  => $parentMenuId,
                    'list_order' => $menu['list_order'],
                    'status'     => 'ACTIVE',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->createPermission($menu['name'], $permissionId, array_filter([$mdRole?->id, $adminRole?->id, $supervisorRole?->id]));
            $this->command->info("  ├─ {$menu['name']}");
        }

        // CRUD permissions for the paylog itself
        foreach (['Add Site Paylog', 'Edit Site Paylog', 'Delete Site Paylog'] as $perm) {
            $this->createPermission($perm, $permissionId, array_filter([$mdRole?->id, $adminRole?->id, $supervisorRole?->id]));
        }

        $this->command->info("\nSite Paylog menu created successfully!");
    }

    private function createPermission(string $name, int &$permissionId, array $roleIds): void
    {
        $existing = DB::table('permissions')->where('name', $name)->first();

        if ($existing) {
            $permId = $existing->id;
        } else {
            $permissionId++;
            $permId = $permissionId;
            DB::table('permissions')->insert([
                'id'         => $permId,
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
