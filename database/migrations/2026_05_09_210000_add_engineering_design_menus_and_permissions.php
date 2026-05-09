<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // ── Structural Design permissions ────────────────────────────────────────
    private array $structuralPerms = [
        'View Structural Designs',
        'Create Structural Design',
        'Edit Structural Design',
        'Delete Structural Design',
        'Approve Structural Design Schedule',
        'Approve Structural Design Stage',
        'Submit Structural Design',
        'Reassign Structural Engineer',
    ];

    // ── Service Design permissions ───────────────────────────────────────────
    private array $servicePerms = [
        'View Service Designs',
        'Create Service Design',
        'Edit Service Design',
        'Delete Service Design',
        'Approve Service Design Schedule',
        'Approve Service Design Stage',
        'Submit Service Design',
        'Reassign Service Engineer',
    ];

    public function up(): void
    {
        $maxOrder = DB::table('menus')->max('list_order') ?? 24;

        // ── 1. Parent menu: Engineering Design ───────────────────────────────
        $parentId = DB::table('menus')->insertGetId([
            'name'       => 'Engineering Design',
            'route'      => '#',
            'icon'       => 'fas fa-hard-hat',
            'parent_id'  => null,
            'list_order' => $maxOrder + 1,
            'status'     => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ── 2. Sub-menus ─────────────────────────────────────────────────────
        DB::table('menus')->insert([
            [
                'name'       => 'Structural Design',
                'route'      => 'structural_design.index',
                'icon'       => 'fas fa-drafting-compass',
                'parent_id'  => $parentId,
                'list_order' => 1,
                'status'     => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Service Design',
                'route'      => 'service_design.index',
                'icon'       => 'fas fa-tools',
                'parent_id'  => $parentId,
                'list_order' => 2,
                'status'     => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // ── 3. Create all permissions ─────────────────────────────────────────
        $allPerms = array_merge($this->structuralPerms, $this->servicePerms);
        $now = now();
        foreach ($allPerms as $perm) {
            DB::table('permissions')->insertOrIgnore([
                'name'       => $perm,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ── 4. Role assignments ───────────────────────────────────────────────

        // System Administrator — full access to everything
        $this->grantToRole('System Administrator', $allPerms);

        // Managing Director + CEO — full approve access on both modules
        $mdCeoPerms = [
            'View Structural Designs', 'Approve Structural Design Schedule',
            'Approve Structural Design Stage', 'Reassign Structural Engineer',
            'Create Structural Design', 'Edit Structural Design', 'Delete Structural Design',
            'Submit Structural Design',
            'View Service Designs', 'Approve Service Design Schedule',
            'Approve Service Design Stage', 'Reassign Service Engineer',
            'Create Service Design', 'Edit Service Design', 'Delete Service Design',
            'Submit Service Design',
        ];
        foreach (['Managing Director', 'CEO', 'Chief Executive Officer'] as $role) {
            $this->grantToRole($role, $mdCeoPerms);
        }

        // Civil Engineer — view + edit stages + submit structural design
        $this->grantToRole('Civil Engineer', [
            'View Structural Designs',
            'Edit Structural Design',
            'Submit Structural Design',
        ]);

        // Service Engineer — view + edit stages + submit service design
        $this->grantToRole('Service Engineer', [
            'View Service Designs',
            'Edit Service Design',
            'Submit Service Design',
        ]);

        // Project Manager — read-only view of both
        $this->grantToRole('Project Manager', [
            'View Structural Designs',
            'View Service Designs',
        ]);

        // Quantity Surveyor — view both approved designs (they get notified)
        $this->grantToRole('Quantity Surveyor (QS)', [
            'View Structural Designs',
            'View Service Designs',
        ]);

        // Sales and Marketing, Business Development Manager — view approved designs
        foreach (['Sales and Marketing', 'Business Development Manager'] as $role) {
            $this->grantToRole($role, [
                'View Structural Designs',
                'View Service Designs',
            ]);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        $parent = DB::table('menus')->where('name', 'Engineering Design')->first();
        if ($parent) {
            DB::table('menus')->where('parent_id', $parent->id)->delete();
            DB::table('menus')->where('id', $parent->id)->delete();
        }

        $allPerms  = array_merge($this->structuralPerms, $this->servicePerms);
        $permIds   = DB::table('permissions')->whereIn('name', $allPerms)->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $permIds)->delete();
        DB::table('permissions')->whereIn('name', $allPerms)->delete();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    private function grantToRole(string $roleName, array $permNames): void
    {
        $roleId = DB::table('roles')->where('name', $roleName)->value('id');
        if (!$roleId) {
            return;
        }
        $permIds = DB::table('permissions')->whereIn('name', $permNames)->pluck('id');
        foreach ($permIds as $permId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permId,
                'role_id'       => $roleId,
            ]);
        }
    }
};
