<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Seed the three canonical cost categories used for finance reporting.
        $now = now();
        foreach (['Material', 'Labour Charge', 'Overhead Expense'] as $name) {
            if (! DB::table('cost_categories')->where('name', $name)->exists()) {
                DB::table('cost_categories')->insert([
                    'name' => $name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // 2. Link project_expenses back to the supplier_receiving that produced it
        //    (used for delivery overheads: loading / offloading / transportation),
        //    plus a free-text subtype tag so we can group them in the receiving view.
        Schema::table('project_expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('project_expenses', 'supplier_receiving_id')) {
                $table->unsignedBigInteger('supplier_receiving_id')->nullable()->after('cost_category_id');
                $table->index('supplier_receiving_id');
            }
            if (! Schema::hasColumn('project_expenses', 'expense_subtype')) {
                $table->string('expense_subtype', 40)->nullable()->after('supplier_receiving_id');
            }
        });

        // 3. Permissions for the new dashboard.
        $perms = [
            ['name' => 'Expenditure Dashboard', 'guard_name' => 'web', 'permission_type' => 'MENU'],
            ['name' => 'Add Receiving Overhead', 'guard_name' => 'web', 'permission_type' => 'CRUD'],
        ];
        foreach ($perms as $p) {
            if (! DB::table('permissions')->where('name', $p['name'])->exists()) {
                DB::table('permissions')->insert(array_merge($p, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }

        // 4. Grant to System Administrator role (id=1) — match the convention in
        //    the existing enhance_project_expenses_table migration.
        $permIds = DB::table('permissions')
            ->whereIn('name', array_column($perms, 'name'))
            ->pluck('id');

        foreach ($permIds as $permId) {
            $exists = DB::table('role_has_permissions')
                ->where('permission_id', $permId)
                ->where('role_id', 1)
                ->exists();
            if (! $exists) {
                DB::table('role_has_permissions')->insert([
                    'permission_id' => $permId,
                    'role_id' => 1,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('project_expenses', function (Blueprint $table) {
            if (Schema::hasColumn('project_expenses', 'supplier_receiving_id')) {
                $table->dropIndex(['supplier_receiving_id']);
                $table->dropColumn('supplier_receiving_id');
            }
            if (Schema::hasColumn('project_expenses', 'expense_subtype')) {
                $table->dropColumn('expense_subtype');
            }
        });

        $permIds = DB::table('permissions')
            ->whereIn('name', ['Expenditure Dashboard', 'Add Receiving Overhead'])
            ->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $permIds)->delete();
        DB::table('permissions')->whereIn('id', $permIds)->delete();

        // Leave seeded cost_categories rows in place — they may already be in use.
    }
};
