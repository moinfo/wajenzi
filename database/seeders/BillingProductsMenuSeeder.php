<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BillingProductsMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // Check if menus already exist
        $existingMenus = DB::table('menus')->whereIn('id', [96, 97])->pluck('id')->toArray();
        
        if (empty($existingMenus)) {
            // Insert Products & Services and Clients menus
            $menus = [
                [
                    'id' => 96,
                    'name' => 'Products & Services',
                    'route' => 'billing.products.index',
                    'icon' => 'fa fa-box',
                    'parent_id' => 89, // Billing main menu
                    'list_order' => 7,
                    'status' => 'ACTIVE',
                    'created_at' => $now,
                    'updated_at' => $now
                ],
                [
                    'id' => 97,
                    'name' => 'Clients',
                    'route' => 'billing.clients.index',
                    'icon' => 'fa fa-users',
                    'parent_id' => 89,
                    'list_order' => 8,
                    'status' => 'ACTIVE',
                    'created_at' => $now,
                    'updated_at' => $now
                ]
            ];

            DB::table('menus')->insert($menus);
            $this->command->info('Billing menus added successfully!');
        } else {
            $this->command->warn('Some menus already exist: ' . implode(', ', $existingMenus));
        }

        // Check if permissions already exist
        $existingPermissions = DB::table('permissions')->whereIn('id', [590, 591])->pluck('id')->toArray();
        
        if (empty($existingPermissions)) {
            // Insert permissions
            $permissions = [
                [
                    'id' => 590,
                    'name' => 'Products & Services',
                    'guard_name' => 'web',
                    'permission_type' => 'MENU',
                    'created_at' => $now,
                    'updated_at' => $now
                ],
                [
                    'id' => 591,
                    'name' => 'Clients',
                    'guard_name' => 'web',
                    'permission_type' => 'MENU',
                    'created_at' => $now,
                    'updated_at' => $now
                ]
            ];

            DB::table('permissions')->insert($permissions);
            $this->command->info('Billing permissions added successfully!');
        } else {
            $this->command->warn('Some permissions already exist: ' . implode(', ', $existingPermissions));
        }

        // Assign permissions to System Administrator role (ID: 1)
        $roleId = 1;
        $newPermissionIds = [590, 591];
        
        $existingAssignments = DB::table('role_has_permissions')
            ->where('role_id', $roleId)
            ->whereIn('permission_id', $newPermissionIds)
            ->pluck('permission_id')
            ->toArray();

        $toAssign = array_diff($newPermissionIds, $existingAssignments);
        
        if (!empty($toAssign)) {
            foreach ($toAssign as $permissionId) {
                DB::table('role_has_permissions')->insert([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId
                ]);
            }
            $this->command->info('Assigned ' . count($toAssign) . ' permissions to System Administrator role');
        } else {
            $this->command->info('All permissions already assigned to System Administrator');
        }
    }
}
