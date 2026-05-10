<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CalculatorMenuAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $adminRoles  = ['System Administrator', 'Managing Director', 'Admin'];
        $salesRoles  = ['System Administrator', 'Managing Director', 'Admin', 'Sales and Marketing'];
        $allStaff    = ['System Administrator', 'Managing Director', 'Admin', 'Sales and Marketing', 'Architect', 'Project Manager'];

        // ── Permissions ──────────────────────────────────────────────────────
        $permissions = [
            // Calculator menu access
            ['name' => 'Calculators',                    'type' => 'MENU', 'roles' => $allStaff],
            ['name' => 'Design Pricing Calculator',      'type' => 'MENU', 'roles' => $allStaff],
            ['name' => 'Site Visit Calculator',          'type' => 'MENU', 'roles' => $salesRoles],

            // Currency settings
            ['name' => 'Currencies',                     'type' => 'MENU', 'roles' => $adminRoles],
            ['name' => 'Add Currency',                   'type' => 'CRUD', 'roles' => $adminRoles],
            ['name' => 'Edit Currency',                  'type' => 'CRUD', 'roles' => $adminRoles],
            ['name' => 'Delete Currency',                'type' => 'CRUD', 'roles' => $adminRoles],

            // Design packages settings
            ['name' => 'Design Packages',                'type' => 'MENU', 'roles' => $adminRoles],
            ['name' => 'Add Design Package',             'type' => 'CRUD', 'roles' => $adminRoles],
            ['name' => 'Edit Design Package',            'type' => 'CRUD', 'roles' => $adminRoles],
            ['name' => 'Delete Design Package',          'type' => 'CRUD', 'roles' => $adminRoles],

            // Design add-ons settings
            ['name' => 'Design Add-ons',                 'type' => 'MENU', 'roles' => $adminRoles],
            ['name' => 'Add Design Addon',               'type' => 'CRUD', 'roles' => $adminRoles],
            ['name' => 'Edit Design Addon',              'type' => 'CRUD', 'roles' => $adminRoles],
            ['name' => 'Delete Design Addon',            'type' => 'CRUD', 'roles' => $adminRoles],

            // Special structures settings
            ['name' => 'Special Structure Rates',        'type' => 'MENU', 'roles' => $adminRoles],
            ['name' => 'Add Special Structure',          'type' => 'CRUD', 'roles' => $adminRoles],
            ['name' => 'Edit Special Structure',         'type' => 'CRUD', 'roles' => $adminRoles],
            ['name' => 'Delete Special Structure',       'type' => 'CRUD', 'roles' => $adminRoles],

            // Site visit locations settings
            ['name' => 'Site Visit Locations',           'type' => 'MENU', 'roles' => $adminRoles],
            ['name' => 'Add Site Visit Location',        'type' => 'CRUD', 'roles' => $adminRoles],
            ['name' => 'Edit Site Visit Location',       'type' => 'CRUD', 'roles' => $adminRoles],
            ['name' => 'Delete Site Visit Location',     'type' => 'CRUD', 'roles' => $adminRoles],
        ];

        foreach ($permissions as $perm) {
            $p = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                ['permission_type' => $perm['type']]
            );
            // Ensure permission_type is set even on existing permissions
            if (!$p->permission_type) {
                $p->update(['permission_type' => $perm['type']]);
            }

            foreach ($perm['roles'] as $roleName) {
                $role = Role::where('name', $roleName)->first();
                if ($role) {
                    DB::table('role_has_permissions')->insertOrIgnore([
                        'permission_id' => $p->id,
                        'role_id'       => $role->id,
                    ]);
                }
            }
        }

        // ── Menus ────────────────────────────────────────────────────────────

        // 1. Top-level "Calculators" menu
        $parent = DB::table('menus')->where('name', 'Calculators')->whereNull('parent_id')->first();
        if (!$parent) {
            $parentId = DB::table('menus')->insertGetId([
                'name'       => 'Calculators',
                'route'      => '',
                'icon'       => 'fa fa-calculator',
                'parent_id'  => null,
                'list_order' => 20,
                'status'     => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $parentId = $parent->id;
        }

        // 2. All Calculators sub-menus (calculators + their config pages)
        $calcMenus = [
            ['name' => 'Design Pricing Calculator', 'route' => 'calculators.design-pricing',             'icon' => 'fa fa-drafting-compass', 'order' => 1],
            ['name' => 'Site Visit Calculator',     'route' => 'calculators.site-visit',                 'icon' => 'fa fa-map-marker-alt',   'order' => 2],
            ['name' => 'Currencies',                'route' => 'hr_settings_currencies',                 'icon' => 'fa fa-coins',            'order' => 10],
            ['name' => 'Design Packages',           'route' => 'hr_settings_design_packages',            'icon' => 'fa fa-box-open',         'order' => 11],
            ['name' => 'Design Add-ons',            'route' => 'hr_settings_design_addons',              'icon' => 'fa fa-plus-square',      'order' => 12],
            ['name' => 'Special Structure Rates',   'route' => 'hr_settings_design_special_structures',  'icon' => 'fa fa-building',         'order' => 13],
            ['name' => 'Site Visit Locations',      'route' => 'hr_settings_site_visit_locations',       'icon' => 'fa fa-map-pin',          'order' => 14],
        ];

        foreach ($calcMenus as $m) {
            $exists = DB::table('menus')->where('name', $m['name'])->where('parent_id', $parentId)->first();
            if (!$exists) {
                DB::table('menus')->insert([
                    'name'       => $m['name'],
                    'route'      => $m['route'],
                    'icon'       => $m['icon'],
                    'parent_id'  => $parentId,
                    'list_order' => $m['order'],
                    'status'     => 'ACTIVE',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 3. Remove those items from Settings if they exist there
        $settingsParent = DB::table('menus')->where('name', 'Settings')->whereNull('parent_id')->first();
        if ($settingsParent) {
            $toRemoveFromSettings = ['Currencies', 'Design Packages', 'Design Add-ons', 'Special Structure Rates', 'Site Visit Locations'];
            DB::table('menus')
                ->where('parent_id', $settingsParent->id)
                ->whereIn('name', $toRemoveFromSettings)
                ->delete();
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Calculator menus and permissions seeded successfully.');
    }
}
