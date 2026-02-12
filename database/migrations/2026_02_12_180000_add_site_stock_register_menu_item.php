<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $role = DB::table('roles')->where('name', 'System Administrator')->first();

        // Insert Site Stock Register menu under Procurement (parent_id = 97)
        // After Material Inspections (list_order 8)
        DB::table('menus')->where('parent_id', 97)->where('list_order', '>=', 9)->increment('list_order');

        DB::table('menus')->insert([
            'name' => 'Site Stock Register',
            'route' => 'stock_register_select',
            'icon' => 'fa fa-warehouse',
            'parent_id' => 97,
            'list_order' => 9,
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $permissionId = DB::table('permissions')->insertGetId([
            'name' => 'Site Stock Register',
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
    }

    public function down(): void
    {
        DB::table('menus')->where('route', 'stock_register_select')->where('parent_id', 97)->delete();

        $menus = DB::table('menus')->where('parent_id', 97)->orderBy('list_order')->get();
        $order = 1;
        foreach ($menus as $menu) {
            DB::table('menus')->where('id', $menu->id)->update(['list_order' => $order++]);
        }

        $permission = DB::table('permissions')->where('name', 'Site Stock Register')->first();
        if ($permission) {
            DB::table('role_has_permissions')->where('permission_id', $permission->id)->delete();
            DB::table('permissions')->where('id', $permission->id)->delete();
        }
    }
};
