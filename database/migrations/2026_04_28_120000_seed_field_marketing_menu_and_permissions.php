<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Menu ──────────────────────────────────────────────────────────
        $existingMenu = DB::table('menus')->where('name', 'Field Marketing')->first();

        if (!$existingMenu) {
            $maxOrder = DB::table('menus')->whereNull('parent_id')->max('list_order') ?? 0;

            DB::table('menus')->insert([
                'name'       => 'Field Marketing',
                'route'      => 'field_marketing.index',
                'icon'       => 'fa fa-map-marked-alt',
                'parent_id'  => null,
                'list_order' => $maxOrder + 1,
                'status'     => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ── 2. Permissions ───────────────────────────────────────────────────
        $permissions = [
            ['name' => 'Field Marketing',                'permission_type' => 'MENU'],
            ['name' => 'Add Field Marketing Session',    'permission_type' => 'CRUD'],
            ['name' => 'Edit Field Marketing Session',   'permission_type' => 'CRUD'],
            ['name' => 'Delete Field Marketing Session', 'permission_type' => 'CRUD'],
            ['name' => 'Add Field Marketing Visit',      'permission_type' => 'CRUD'],
            ['name' => 'Edit Field Marketing Visit',     'permission_type' => 'CRUD'],
            ['name' => 'Delete Field Marketing Visit',   'permission_type' => 'CRUD'],
            ['name' => 'Set Field Marketing Target',     'permission_type' => 'CRUD'],
            ['name' => 'Manage Field Marketing Services','permission_type' => 'SETTING'],
        ];

        foreach ($permissions as $p) {
            $exists = DB::table('permissions')->where('name', $p['name'])->exists();
            if (!$exists) {
                DB::table('permissions')->insert([
                    'name'            => $p['name'],
                    'guard_name'      => 'web',
                    'permission_type' => $p['permission_type'],
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
        }

        // ── 3. Assign all FM permissions to System Administrator ─────────────
        $adminRoleId = DB::table('roles')->where('name', 'System Administrator')->value('id');

        if ($adminRoleId) {
            $fmPermIds = DB::table('permissions')
                ->where('name', 'like', '%Field Marketing%')
                ->pluck('id');

            foreach ($fmPermIds as $permId) {
                $exists = DB::table('role_has_permissions')
                    ->where('role_id', $adminRoleId)
                    ->where('permission_id', $permId)
                    ->exists();

                if (!$exists) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $permId,
                        'role_id'       => $adminRoleId,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // Remove role_has_permissions entries
        $fmPermIds = DB::table('permissions')
            ->where('name', 'like', '%Field Marketing%')
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $fmPermIds)->delete();

        // Remove permissions
        DB::table('permissions')->where('name', 'like', '%Field Marketing%')->delete();

        // Remove menu
        DB::table('menus')->where('name', 'Field Marketing')->delete();
    }
};
