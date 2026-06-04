<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Grant the Site Paylog permissions to the Civil Engineer and Procurement
     * Officer roles. Looked up by name (not hard-coded IDs) so it stays correct
     * across environments. Idempotent via insertOrIgnore.
     */
    private array $roleNames = ['Civil Engineer', 'Procurement Officer'];

    private array $permissionNames = [
        'Site Paylog',
        'Daily Payments',
        'Daily Payment Report',
        'Monthly Payment Report',
        'Payment Channels',
        'Add Site Paylog',
        'Edit Site Paylog',
        'Delete Site Paylog',
    ];

    public function up(): void
    {
        $roleIds = DB::table('roles')->whereIn('name', $this->roleNames)->pluck('id', 'name');
        $permIds = DB::table('permissions')->whereIn('name', $this->permissionNames)->pluck('id');

        foreach ($this->roleNames as $roleName) {
            $roleId = $roleIds[$roleName] ?? null;
            if (!$roleId) {
                // Role missing in this environment — skip rather than fail the migration.
                continue;
            }

            foreach ($permIds as $permId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permId,
                    'role_id'       => $roleId,
                ]);
            }
        }

        // Flush Spatie's cached permission map so the grants take effect immediately.
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        $roleIds = DB::table('roles')->whereIn('name', $this->roleNames)->pluck('id');
        $permIds = DB::table('permissions')->whereIn('name', $this->permissionNames)->pluck('id');

        DB::table('role_has_permissions')
            ->whereIn('role_id', $roleIds)
            ->whereIn('permission_id', $permIds)
            ->delete();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
