<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill approval_levels.role_id by matching legacy user_groups → Spatie roles.
 *
 * Lookup is by name so the migration is portable across environments (dev/stage/prod)
 * where the autoincrement ids of user_groups and roles likely differ but the
 * semantic names are stable. Only rows where role_id is NULL are touched, so the
 * migration is idempotent — safe to re-run if any row was already migrated
 * manually via the UI.
 *
 * Mapping (legacy user_group name → Spatie role name):
 *   CHEIF EXECUTIVE OFFICER  → CEO
 *   System Admin             → System Administrator
 *   Managing Director        → Managing Director
 *   Human resources Manager  → HR Generalist
 *   Finance Accountant       → Accountant
 *
 * Any approval_level whose user_group is not in this map (or whose mapped role
 * doesn't exist on the target environment) is left untouched — the admin can
 * pick the role via /settings/approval_levels and the UI will display a
 * "(legacy)" warning badge until they do.
 */
return new class extends Migration {
    public function up(): void
    {
        $mapping = [
            'CHEIF EXECUTIVE OFFICER' => 'CEO',
            'System Admin'            => 'System Administrator',
            'Managing Director'       => 'Managing Director',
            'Human resources Manager' => 'HR Generalist',
            'Finance Accountant'      => 'Accountant',
        ];

        foreach ($mapping as $groupName => $roleName) {
            $group = DB::table('user_groups')->where('name', $groupName)->first();
            $role  = DB::table('roles')->where('name', $roleName)->first();

            if (!$group || !$role) {
                continue;
            }

            DB::table('approval_levels')
                ->whereNull('role_id')
                ->where('user_group_id', $group->id)
                ->where('order', '>', 0)
                ->update(['role_id' => $role->id]);
        }
    }

    public function down(): void
    {
        // Reversible only by undoing the column itself (handled in the prior migration).
        // We intentionally do not null out role_id here because an admin may have
        // edited some rows via the UI after this backfill ran — we can't tell
        // which role assignments came from this migration vs. from the UI.
    }
};
