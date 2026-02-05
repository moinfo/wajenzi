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

        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'project_id')) {
                $table->unsignedBigInteger('project_id')->nullable()->after('id');
            }

            if (!Schema::hasColumn('purchases', 'material_request_id')) {
                $table->unsignedBigInteger('material_request_id')->nullable()->after('project_id');
            }

            if (!Schema::hasColumn('purchases', 'quotation_comparison_id')) {
                $table->unsignedBigInteger('quotation_comparison_id')->nullable()->after('material_request_id');
            }

            if (!Schema::hasColumn('purchases', 'expected_delivery_date')) {
                $table->date('expected_delivery_date')->nullable()->after('invoice_date');
            }

            if (!Schema::hasColumn('purchases', 'delivery_address')) {
                $table->text('delivery_address')->nullable()->after('expected_delivery_date');
            }

            if (!Schema::hasColumn('purchases', 'payment_terms')) {
                $table->string('payment_terms', 100)->nullable()->after('delivery_address');
            }

            if (!Schema::hasColumn('purchases', 'notes')) {
                $table->text('notes')->nullable()->after('payment_terms');
            }
        });

        Schema::table('purchases', function (Blueprint $table) {
            if (!$this->hasForeignKey('purchases', 'purchases_project_id_foreign')) {
                $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
            }
            if (!$this->hasForeignKey('purchases', 'purchases_material_request_id_foreign')) {
                $table->foreign('material_request_id')->references('id')->on('project_material_requests')->nullOnDelete();
            }
            if (!$this->hasForeignKey('purchases', 'purchases_quotation_comparison_id_foreign')) {
                $table->foreign('quotation_comparison_id')->references('id')->on('quotation_comparisons')->nullOnDelete();
            }
        });

        Schema::table('purchases', function (Blueprint $table) {
            if (!$this->hasIndex('purchases', 'purchases_project_id_material_request_id_index')) {
                $table->index(['project_id', 'material_request_id']);
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
        Schema::table('purchases', function (Blueprint $table) {
            // Drop foreign keys first
            $foreignKeys = ['project_id', 'material_request_id', 'quotation_comparison_id'];

            foreach ($foreignKeys as $column) {
                if (Schema::hasColumn('purchases', $column)) {
                    $table->dropForeign([$column]);
                    $table->dropColumn($column);
                }
            }

            // Drop other columns
            $columns = ['expected_delivery_date', 'delivery_address', 'payment_terms', 'notes'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('purchases', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
