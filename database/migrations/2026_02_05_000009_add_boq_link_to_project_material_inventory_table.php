<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('project_material_inventory', function (Blueprint $table) {
            if (!Schema::hasColumn('project_material_inventory', 'boq_item_id')) {
                $table->unsignedBigInteger('boq_item_id')->nullable()->after('material_id');
            }

            if (!Schema::hasColumn('project_material_inventory', 'quantity_used')) {
                $table->decimal('quantity_used', 10, 2)->default(0)->after('quantity');
            }

            if (!Schema::hasColumn('project_material_inventory', 'quantity_available')) {
                $table->decimal('quantity_available', 10, 2)
                    ->virtualAs('quantity - quantity_used')
                    ->after('quantity_used');
            }

            if (!Schema::hasColumn('project_material_inventory', 'last_updated_at')) {
                $table->timestamp('last_updated_at')->nullable()->after('last_updated');
            }

            if (!Schema::hasColumn('project_material_inventory', 'minimum_stock_level')) {
                $table->decimal('minimum_stock_level', 10, 2)->default(0)->after('quantity_available');
            }
        });

        Schema::table('project_material_inventory', function (Blueprint $table) {
            if (!$this->hasForeignKey('project_material_inventory', 'project_material_inventory_boq_item_id_foreign')) {
                $table->foreign('boq_item_id')->references('id')->on('project_boq_items')->nullOnDelete();
            }
        });

        Schema::table('project_material_inventory', function (Blueprint $table) {
            if (!$this->hasIndex('project_material_inventory', 'project_material_inventory_project_id_boq_item_id_index')) {
                $table->index(['project_id', 'boq_item_id']);
            }
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function hasForeignKey(string $table, string $keyName): bool
    {
        $keys = DB::select("
            SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$table]);

        return collect($keys)->contains(fn($key) => $key->CONSTRAINT_NAME === $keyName);
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_material_inventory', function (Blueprint $table) {
            // Drop foreign key first
            if (Schema::hasColumn('project_material_inventory', 'boq_item_id')) {
                $table->dropForeign(['boq_item_id']);
                $table->dropColumn('boq_item_id');
            }

            // Drop other columns
            $columns = ['quantity_used', 'quantity_available', 'last_updated_at', 'minimum_stock_level'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('project_material_inventory', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
