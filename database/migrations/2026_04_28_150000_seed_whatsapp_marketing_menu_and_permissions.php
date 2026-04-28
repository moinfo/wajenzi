<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Menu ──────────────────────────────────────────────────────────
        if (!DB::table('menus')->where('name', 'WhatsApp Marketing')->exists()) {
            $maxOrder = DB::table('menus')->whereNull('parent_id')->max('list_order') ?? 0;
            DB::table('menus')->insert([
                'name'       => 'WhatsApp Marketing',
                'route'      => 'whatsapp_marketing.index',
                'icon'       => 'fa fa-whatsapp',
                'parent_id'  => null,
                'list_order' => $maxOrder + 1,
                'status'     => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ── 2. Permissions ───────────────────────────────────────────────────
        $permissions = [
            ['name' => 'WhatsApp Marketing',              'permission_type' => 'MENU'],
            ['name' => 'Add WhatsApp Contact',            'permission_type' => 'CRUD'],
            ['name' => 'Edit WhatsApp Contact',           'permission_type' => 'CRUD'],
            ['name' => 'Delete WhatsApp Contact',         'permission_type' => 'CRUD'],
            ['name' => 'Manage WhatsApp Campaigns',       'permission_type' => 'CRUD'],
        ];

        foreach ($permissions as $p) {
            if (!DB::table('permissions')->where('name', $p['name'])->exists()) {
                DB::table('permissions')->insert([
                    'name'            => $p['name'],
                    'guard_name'      => 'web',
                    'permission_type' => $p['permission_type'],
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
        }

        // ── 3. Assign all WA permissions to System Administrator ─────────────
        $adminRoleId = DB::table('roles')->where('name', 'System Administrator')->value('id');

        if ($adminRoleId) {
            $permIds = DB::table('permissions')
                ->where(function ($q) {
                    $q->where('name', 'like', '%WhatsApp%')
                      ->orWhere('name', 'like', '%whatsapp%');
                })
                ->pluck('id');

            foreach ($permIds as $permId) {
                if (!DB::table('role_has_permissions')->where('role_id', $adminRoleId)->where('permission_id', $permId)->exists()) {
                    DB::table('role_has_permissions')->insert(['permission_id' => $permId, 'role_id' => $adminRoleId]);
                }
            }
        }
    }

    public function down(): void
    {
        $permIds = DB::table('permissions')
            ->where('name', 'like', '%WhatsApp%')
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permIds)->delete();
        DB::table('permissions')->where('name', 'like', '%WhatsApp%')->delete();
        DB::table('menus')->where('name', 'WhatsApp Marketing')->delete();
    }
};
