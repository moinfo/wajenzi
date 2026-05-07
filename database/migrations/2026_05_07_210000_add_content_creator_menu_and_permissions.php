<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $permissions = [
        // Menu gate (must match menu name exactly)
        'Content Creator',
        // Task CRUD
        'Create Content Task',
        'Edit Content Task',
        'Delete Content Task',
        'View Content Tasks',
        // Workflow actions
        'Update Task Progress',
        'Add Task Comment',
        'Approve Content Task',
        // Management
        'Manage Crew',
        'Manage Platform Targets',
    ];

    public function up(): void
    {
        // ── 1. Insert menu item ───────────────────────────────────────
        $maxOrder = DB::table('menus')->max('list_order') ?? 23;

        DB::table('menus')->insertOrIgnore([
            'name'       => 'Content Creator',
            'route'      => 'content_creator.index',
            'icon'       => 'fas fa-video',
            'parent_id'  => null,
            'list_order' => $maxOrder + 1,
            'status'     => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ── 2. Create permissions ─────────────────────────────────────
        $now = now();
        foreach ($this->permissions as $perm) {
            DB::table('permissions')->insertOrIgnore([
                'name'       => $perm,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ── 3. Assign all permissions to System Administrator ─────────
        $adminRoleId = DB::table('roles')->where('name', 'System Administrator')->value('id');
        if ($adminRoleId) {
            $this->assignPermissionsToRole($adminRoleId, $this->permissions);
        }

        // ── 4. Assign appropriate permissions to content creator roles ─
        $creatorPerms = [
            'Content Creator',
            'View Content Tasks',
            'Update Task Progress',
            'Add Task Comment',
        ];
        $managerPerms = array_merge($creatorPerms, [
            'Create Content Task',
            'Edit Content Task',
            'Approve Content Task',
            'Manage Platform Targets',
        ]);

        foreach (['Content creator and IT', 'Digital Marketing and Content Creator'] as $roleName) {
            $roleId = DB::table('roles')->where('name', $roleName)->value('id');
            if ($roleId) {
                $this->assignPermissionsToRole($roleId, $creatorPerms);
            }
        }

        // Business Development Manager gets full management access
        $bdmId = DB::table('roles')->where('name', 'Business Development Manager')->value('id');
        if ($bdmId) {
            $this->assignPermissionsToRole($bdmId, $managerPerms);
        }

        // Clear Spatie permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table('menus')->where('name', 'Content Creator')->delete();

        $permIds = DB::table('permissions')->whereIn('name', $this->permissions)->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $permIds)->delete();
        DB::table('permissions')->whereIn('name', $this->permissions)->delete();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    private function assignPermissionsToRole(int $roleId, array $permNames): void
    {
        $permIds = DB::table('permissions')->whereIn('name', $permNames)->pluck('id');
        foreach ($permIds as $permId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permId,
                'role_id'       => $roleId,
            ]);
        }
    }
};
