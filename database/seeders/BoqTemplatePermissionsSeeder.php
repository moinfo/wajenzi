<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class BoqTemplatePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // BOQ Template System Permissions with permission_type
        $permissions = [
            // Menu Access
            ['name' => 'BOQ Templates', 'permission_type' => 'MENU'],

            // Sub Menu Access
            ['name' => 'Building Types', 'permission_type' => 'MENU'],
            ['name' => 'BOQ Item Categories', 'permission_type' => 'MENU'],
            ['name' => 'Construction Stages', 'permission_type' => 'MENU'],
            ['name' => 'Activities', 'permission_type' => 'MENU'],
            ['name' => 'Sub-Activities', 'permission_type' => 'MENU'],
            ['name' => 'BOQ Items', 'permission_type' => 'MENU'],
            ['name' => 'BOQ Template Design', 'permission_type' => 'MENU'],

            // Building Types CRUD
            ['name' => 'Add Building Type', 'permission_type' => 'CRUD'],
            ['name' => 'Edit Building Type', 'permission_type' => 'CRUD'],
            ['name' => 'Delete Building Type', 'permission_type' => 'CRUD'],

            // BOQ Item Categories CRUD
            ['name' => 'Add BOQ Item Category', 'permission_type' => 'CRUD'],
            ['name' => 'Edit BOQ Item Category', 'permission_type' => 'CRUD'],
            ['name' => 'Delete BOQ Item Category', 'permission_type' => 'CRUD'],

            // Construction Stages CRUD
            ['name' => 'Add Construction Stage', 'permission_type' => 'CRUD'],
            ['name' => 'Edit Construction Stage', 'permission_type' => 'CRUD'],
            ['name' => 'Delete Construction Stage', 'permission_type' => 'CRUD'],

            // Activities CRUD
            ['name' => 'Add Activity', 'permission_type' => 'CRUD'],
            ['name' => 'Edit Activity', 'permission_type' => 'CRUD'],
            ['name' => 'Delete Activity', 'permission_type' => 'CRUD'],

            // Sub-Activities CRUD
            ['name' => 'Add Sub Activity', 'permission_type' => 'CRUD'],
            ['name' => 'Edit Sub Activity', 'permission_type' => 'CRUD'],
            ['name' => 'Delete Sub Activity', 'permission_type' => 'CRUD'],

            // BOQ Items CRUD
            ['name' => 'Add BOQ Item', 'permission_type' => 'CRUD'],
            ['name' => 'Edit BOQ Item', 'permission_type' => 'CRUD'],
            ['name' => 'Delete BOQ Item', 'permission_type' => 'CRUD'],

            // BOQ Templates CRUD
            ['name' => 'Add BOQ Template', 'permission_type' => 'CRUD'],
            ['name' => 'Edit BOQ Template', 'permission_type' => 'CRUD'],
            ['name' => 'Delete BOQ Template', 'permission_type' => 'CRUD'],

            // BOQ Template Special Operations
            ['name' => 'Build BOQ Template', 'permission_type' => 'CRUD'],
            ['name' => 'Export BOQ Template', 'permission_type' => 'CRUD'],
            ['name' => 'Import BOQ Template', 'permission_type' => 'CRUD'],
            ['name' => 'Use BOQ Template', 'permission_type' => 'CRUD'],
            ['name' => 'Generate BOQ from Template', 'permission_type' => 'CRUD'],
            ['name' => 'Customize BOQ Template', 'permission_type' => 'CRUD'],
        ];

        // Create permissions with permission_type
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

        // Get or create admin role
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);

        // Extract permission names for role assignment
        $permissionNames = array_column($permissions, 'name');

        // Assign all BOQ permissions to admin
        $adminRole->givePermissionTo($permissionNames);

        // Create BOQ Manager role with specific permissions
        $boqManagerRole = Role::firstOrCreate(['name' => 'BOQ Manager']);
        $boqManagerPermissions = [
            'BOQ Templates', // Menu access
            'Building Type', 'Add Building Type', 'Edit Building Type', 'Delete Building Type',
            'BOQ Item Category', 'Add BOQ Item Category', 'Edit BOQ Item Category', 'Delete BOQ Item Category',
            'Construction Stage', 'Add Construction Stage', 'Edit Construction Stage', 'Delete Construction Stage',
            'Activity', 'Add Activity', 'Edit Activity', 'Delete Activity',
            'Sub Activity', 'Add Sub Activity', 'Edit Sub Activity', 'Delete Sub Activity',
            'BOQ Item', 'Add BOQ Item', 'Edit BOQ Item', 'Delete BOQ Item',
            'BOQ Template', 'Add BOQ Template', 'Edit BOQ Template', 'Delete BOQ Template',
            'Build BOQ Template', 'Export BOQ Template', 'Import BOQ Template',
            'Use BOQ Template', 'Generate BOQ from Template', 'Customize BOQ Template',
        ];
        $boqManagerRole->givePermissionTo($boqManagerPermissions);

        // Create Project Manager role with template usage permissions
        $projectManagerRole = Role::firstOrCreate(['name' => 'Project Manager']);
        $projectManagerPermissions = [
            'BOQ Templates', // Menu access
            'Building Type', 'BOQ Item Category', 'Construction Stage',
            'Activity', 'Sub Activity', 'BOQ Item', 'BOQ Template',
            'Use BOQ Template', 'Generate BOQ from Template', 'Customize BOQ Template',
        ];
        $projectManagerRole->givePermissionTo($projectManagerPermissions);

        // Create menu entries
        $this->createMenus();

        echo "BOQ Template permissions created successfully!\n";
        echo "Created " . count($permissions) . " permissions\n";
        echo "Assigned permissions to Admin, BOQ Manager, and Project Manager roles\n";
        echo "Created BOQ Template menu structure\n";
    }

    private function createMenus()
    {
        // Get the next available menu ID
        $maxId = DB::table('menus')->max('id') ?? 0;

        // Main BOQ Templates menu (parent)
        $boqTemplatesMenuId = $maxId + 1;
        DB::table('menus')->insertOrIgnore([
            'id' => $boqTemplatesMenuId,
            'name' => 'BOQ Templates',
            'route' => null, // Parent menu, no direct route
            'icon' => 'fa fa-building',
            'parent_id' => null,
            'list_order' => 50, // Adjust as needed
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Sub-menus under BOQ Templates
        $subMenus = [
            [
                'name' => 'Building Types',
                'route' => 'hr_settings_building_types',
                'icon' => 'fa fa-home',
                'list_order' => 1
            ],
            [
                'name' => 'Item Categories',
                'route' => 'hr_settings_boq_item_categories',
                'icon' => 'fa fa-list',
                'list_order' => 2
            ],
            [
                'name' => 'Construction Stages',
                'route' => 'hr_settings_construction_stages',
                'icon' => 'fa fa-layer-group',
                'list_order' => 3
            ],
            [
                'name' => 'Activities',
                'route' => 'hr_settings_activities',
                'icon' => 'fa fa-wrench',
                'list_order' => 4
            ],
            [
                'name' => 'Sub-Activities',
                'route' => 'hr_settings_sub_activities',
                'icon' => 'fa fa-puzzle-piece',
                'list_order' => 5
            ],
            [
                'name' => 'BOQ Items',
                'route' => 'hr_settings_boq_items',
                'icon' => 'fa fa-shopping-bag',
                'list_order' => 6
            ],
            [
                'name' => 'BOQ Templates Design',
                'route' => 'hr_settings_boq_templates',
                'icon' => 'fa fa-file-text',
                'list_order' => 7
            ]
        ];

        foreach ($subMenus as $index => $menu) {
            $subMenuId = $boqTemplatesMenuId + $index + 1;
            DB::table('menus')->insertOrIgnore([
                'id' => $subMenuId,
                'name' => $menu['name'],
                'route' => $menu['route'],
                'icon' => $menu['icon'],
                'parent_id' => $boqTemplatesMenuId,
                'list_order' => $menu['list_order'],
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
