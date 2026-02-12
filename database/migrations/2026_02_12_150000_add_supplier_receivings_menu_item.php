<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $role = DB::table('roles')->where('name', 'System Administrator')->first();

        // Insert Record Deliveries menu under Procurement (parent_id = 97)
        // Between Purchase Orders (list_order 5) and Material Inspections (list_order 6)
        DB::table('menus')->where('parent_id', 97)->where('list_order', '>=', 6)->increment('list_order');

        DB::table('menus')->insert([
            'name' => 'Record Deliveries',
            'route' => 'record_deliveries',
            'icon' => 'fa fa-dolly',
            'parent_id' => 97,
            'list_order' => 6,
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $permissionId = DB::table('permissions')->insertGetId([
            'name' => 'Record Deliveries',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($role) {
            DB::table('role_has_permissions')->insert([
                'permission_id' => $permissionId,
                'role_id' => $role->id,
            ]);
        }

        // Insert Supplier Receivings menu (list_order 7, after Record Deliveries)
        DB::table('menus')->where('parent_id', 97)->where('list_order', '>=', 7)->increment('list_order');

        DB::table('menus')->insert([
            'name' => 'Supplier Receivings',
            'route' => 'supplier_receivings_procurement',
            'icon' => 'fa fa-truck',
            'parent_id' => 97,
            'list_order' => 7,
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $permissionId2 = DB::table('permissions')->insertGetId([
            'name' => 'Supplier Receivings',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($role) {
            DB::table('role_has_permissions')->insert([
                'permission_id' => $permissionId2,
                'role_id' => $role->id,
            ]);
        }
    }

    public function down(): void
    {
        foreach (['record_deliveries', 'supplier_receivings_procurement'] as $route) {
            DB::table('menus')->where('route', $route)->where('parent_id', 97)->delete();
        }

        // Reset list_order for remaining items
        $menus = DB::table('menus')->where('parent_id', 97)->orderBy('list_order')->get();
        $order = 1;
        foreach ($menus as $menu) {
            DB::table('menus')->where('id', $menu->id)->update(['list_order' => $order++]);
        }

        foreach (['Record Deliveries', 'Supplier Receivings'] as $name) {
            $permission = DB::table('permissions')->where('name', $name)->first();
            if ($permission) {
                DB::table('role_has_permissions')->where('permission_id', $permission->id)->delete();
                DB::table('permissions')->where('id', $permission->id)->delete();
            }
        }
    }
};
