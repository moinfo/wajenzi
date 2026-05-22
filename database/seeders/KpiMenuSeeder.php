<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Creates the top-level "Performance" menu with submenus, plus the Spatie
 * permissions that gate each menu item.
 *
 * Permission distribution:
 *   "Performance"                 → every employee role (so the menu shows)
 *   "My Performance Reviews"      → every employee role
 *   "Awaiting Performance Review" → supervisors / MD / CEO (anyone who can approve)
 *   "All Performance Reviews"     → HR / Admin / MD / CEO
 *   "Manage KPI Templates"        → System Admin only
 */
class KpiMenuSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating Performance / KPI menu structure...');

        // All employee roles get to see the parent and "My Reviews"
        $employeeRoleNames = [
            'System Administrator', 'Managing Director', 'CEO', 'Chief Executive Officer',
            'HR Generalist', 'Accountant', 'Site Supervisor', 'Procurement Officer',
            'Sales Manager', 'Digital Marketing and Content Creator', 'Architect',
            'Civil Engineer', 'Sales and Marketing', 'Quantity Surveyor (QS)',
            'Project Manager', 'Service Engineer', 'General Manager',
        ];
        $employeeRoleIds = DB::table('roles')->whereIn('name', $employeeRoleNames)->pluck('id')->toArray();

        $reviewerRoleIds = DB::table('roles')->whereIn('name', [
            'Managing Director', 'CEO', 'Chief Executive Officer',
        ])->pluck('id')->toArray();
        // Plus anyone who might be a personal supervisor — covered by attaching to all employee roles below.
        $reviewerRoleIds = array_unique(array_merge($reviewerRoleIds, $employeeRoleIds));

        $adminHrRoleIds = DB::table('roles')->whereIn('name', [
            'System Administrator', 'Managing Director', 'CEO', 'Chief Executive Officer', 'HR Generalist',
        ])->pluck('id')->toArray();

        $adminOnlyRoleIds = DB::table('roles')->whereIn('name', ['System Administrator'])->pluck('id')->toArray();

        // Parent menu
        $parentId = $this->upsertMenu('Performance', '', 'fa fa-chart-line', null, 12);
        $this->createPermission('Performance', $employeeRoleIds);
        $this->command->info("Parent menu: Performance (ID: {$parentId})");

        // Submenus
        $submenus = [
            ['name' => 'My Performance Reviews',     'route' => 'performance.index',    'icon' => 'fa fa-user-edit',     'order' => 1, 'roles' => $employeeRoleIds],
            ['name' => 'Awaiting Performance Review','route' => 'performance.index',    'icon' => 'fa fa-clipboard-check','order' => 2, 'roles' => $reviewerRoleIds],
            ['name' => 'All Performance Reviews',    'route' => 'performance.index',    'icon' => 'fa fa-list',          'order' => 3, 'roles' => $adminHrRoleIds],
            ['name' => 'Manage KPI Templates',       'route' => 'performance.index',    'icon' => 'fa fa-cog',           'order' => 4, 'roles' => $adminOnlyRoleIds],
        ];

        foreach ($submenus as $m) {
            $id = $this->upsertMenu($m['name'], $m['route'], $m['icon'], $parentId, $m['order']);
            $this->createPermission($m['name'], $m['roles']);
            $this->command->info("  └─ {$m['name']} (ID: {$id})");
        }

        $this->command->info("\nPerformance / KPI menu created successfully!");
    }

    /**
     * Idempotent upsert by name. Returns the menu id.
     */
    private function upsertMenu(string $name, string $route, string $icon, ?int $parentId, int $order): int
    {
        $existing = DB::table('menus')->where('name', $name)->first();
        if ($existing) {
            DB::table('menus')->where('id', $existing->id)->update([
                'route'      => $route,
                'icon'       => $icon,
                'parent_id'  => $parentId,
                'list_order' => $order,
                'status'     => 1,
                'updated_at' => now(),
            ]);
            return (int) $existing->id;
        }
        return (int) DB::table('menus')->insertGetId([
            'name'       => $name,
            'route'      => $route,
            'icon'       => $icon,
            'parent_id'  => $parentId,
            'list_order' => $order,
            'status'     => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Create the permission (if missing) and attach to the given roles.
     */
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
