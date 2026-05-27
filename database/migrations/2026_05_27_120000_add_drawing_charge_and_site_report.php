<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds the "Drawing Charge" cost category (used by the Site Cost Report's
 * drawing-charges column) and a "Site Cost Report" menu permission, granted to
 * System Administrator (role 1). Mirrors the seeding style of the
 * 2026_05_09_120000 finance-pieces migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // New cost category so drawing/design charges can be recorded as a
        // ProjectExpense and rolled up per site, just like Overhead Expense.
        if (! DB::table('cost_categories')->where('name', 'Drawing Charge')->exists()) {
            DB::table('cost_categories')->insert([
                'name'       => 'Drawing Charge',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Menu permission for the report; granted to System Administrator (id=1).
        if (! DB::table('permissions')->where('name', 'Site Cost Report')->exists()) {
            DB::table('permissions')->insert([
                'name'            => 'Site Cost Report',
                'guard_name'      => 'web',
                'permission_type' => 'MENU',
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }

        $permId = DB::table('permissions')->where('name', 'Site Cost Report')->value('id');
        if ($permId && ! DB::table('role_has_permissions')
            ->where('permission_id', $permId)->where('role_id', 1)->exists()) {
            DB::table('role_has_permissions')->insert([
                'permission_id' => $permId,
                'role_id'       => 1,
            ]);
        }
    }

    public function down(): void
    {
        $permId = DB::table('permissions')->where('name', 'Site Cost Report')->value('id');
        if ($permId) {
            DB::table('role_has_permissions')->where('permission_id', $permId)->delete();
            DB::table('permissions')->where('id', $permId)->delete();
        }
        // Leave the 'Drawing Charge' cost_categories row in place — it may already be in use.
    }
};
