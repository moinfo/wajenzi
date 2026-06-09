<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Authoritatively set the Site Paylog menu/sub-menu permissions per role
     * (strict separation of duties). The sidebar (components/sidebar.blade.php)
     * shows a menu only when the user holds a permission named identically to
     * the menu, so each permission name below mirrors a menu name.
     *
     * This is a "set", not a "merge": for every permission listed it clears the
     * existing role grants and re-applies exactly the matrix — so it also strips
     * stray/legacy grants. Idempotent and re-runnable.
     */
    private function matrix(): array
    {
        $initiators = ['Site Supervisor', 'Civil Engineer', 'System Administrator'];
        $everyone   = ['Site Supervisor', 'Civil Engineer', 'Procurement Officer', 'Managing Director', 'Accountant', 'System Administrator'];

        return [
            'Site Paylog'            => $everyone,
            'Daily Payments'         => $initiators,
            'Payment Requests'       => $everyone,
            'Daily Payment Report'   => $everyone,
            'Monthly Payment Report' => $everyone,
            'Payment Channels'       => ['Procurement Officer', 'Accountant', 'System Administrator'],
            'Add Site Paylog'        => $initiators,
            'Edit Site Paylog'       => $initiators,
            'Delete Site Paylog'     => $initiators,
            'Process Site Payment'   => ['Managing Director', 'Accountant', 'System Administrator'],
        ];
    }

    public function up(): void
    {
        $roleIds = DB::table('roles')->pluck('id', 'name');

        foreach ($this->matrix() as $permName => $roleNames) {
            $permId = $this->ensurePermission($permName);

            // Clean slate for this permission, then apply the matrix.
            DB::table('role_has_permissions')->where('permission_id', $permId)->delete();

            foreach ($roleNames as $roleName) {
                $roleId = $roleIds[$roleName] ?? null;
                if ($roleId) {
                    DB::table('role_has_permissions')->insertOrIgnore([
                        'permission_id' => $permId,
                        'role_id'       => $roleId,
                    ]);
                }
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Non-reversible role re-assignment; leave grants in place on rollback.
    }

    private function ensurePermission(string $name): int
    {
        $existing = DB::table('permissions')->where('name', $name)->first();
        if ($existing) {
            return $existing->id;
        }

        $id = (DB::table('permissions')->max('id') ?? 0) + 1;
        DB::table('permissions')->insert([
            'id'         => $id,
            'name'       => $name,
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }
};
