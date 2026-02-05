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
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Add columns without foreign keys first
        Schema::table('project_material_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('project_material_requests', 'boq_item_id')) {
                $table->unsignedBigInteger('boq_item_id')->nullable()->after('project_id');
            }

            if (!Schema::hasColumn('project_material_requests', 'construction_phase_id')) {
                $table->unsignedBigInteger('construction_phase_id')->nullable()->after('boq_item_id');
            }

            if (!Schema::hasColumn('project_material_requests', 'request_number')) {
                $table->string('request_number', 50)->nullable()->after('id');
            }

            if (!Schema::hasColumn('project_material_requests', 'quantity_requested')) {
                $table->decimal('quantity_requested', 10, 2)->default(0)->after('status');
            }

            if (!Schema::hasColumn('project_material_requests', 'quantity_approved')) {
                $table->decimal('quantity_approved', 10, 2)->nullable()->after('quantity_requested');
            }

            if (!Schema::hasColumn('project_material_requests', 'unit')) {
                $table->string('unit', 20)->nullable()->after('quantity_approved');
            }

            if (!Schema::hasColumn('project_material_requests', 'required_date')) {
                $table->date('required_date')->nullable()->after('unit');
            }

            if (!Schema::hasColumn('project_material_requests', 'purpose')) {
                $table->text('purpose')->nullable()->after('required_date');
            }

            if (!Schema::hasColumn('project_material_requests', 'priority')) {
                $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium')->after('purpose');
            }

            if (!Schema::hasColumn('project_material_requests', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('requester_id');
            }
        });

        // Add foreign keys separately
        Schema::table('project_material_requests', function (Blueprint $table) {
            if (!$this->hasForeignKey('project_material_requests', 'project_material_requests_boq_item_id_foreign')) {
                $table->foreign('boq_item_id')->references('id')->on('project_boq_items')->nullOnDelete();
            }
            if (!$this->hasForeignKey('project_material_requests', 'project_material_requests_construction_phase_id_foreign')) {
                $table->foreign('construction_phase_id')->references('id')->on('project_construction_phases')->nullOnDelete();
            }
            if (!$this->hasForeignKey('project_material_requests', 'project_material_requests_approved_by_foreign')) {
                $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            }
        });

        // Add index
        Schema::table('project_material_requests', function (Blueprint $table) {
            if (!$this->hasIndex('project_material_requests', 'project_material_requests_request_number_index')) {
                $table->index('request_number');
            }
        });

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Check if a foreign key exists.
     */
    private function hasForeignKey(string $table, string $keyName): bool
    {
        $keys = DB::select("
            SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$table]);

        return collect($keys)->contains(fn($key) => $key->CONSTRAINT_NAME === $keyName);
    }

    /**
     * Check if an index exists.
     */
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
        Schema::table('project_material_requests', function (Blueprint $table) {
            // Drop foreign keys first
            if (Schema::hasColumn('project_material_requests', 'boq_item_id')) {
                $table->dropForeign(['boq_item_id']);
                $table->dropColumn('boq_item_id');
            }

            if (Schema::hasColumn('project_material_requests', 'construction_phase_id')) {
                $table->dropForeign(['construction_phase_id']);
                $table->dropColumn('construction_phase_id');
            }

            if (Schema::hasColumn('project_material_requests', 'approved_by')) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn('approved_by');
            }

            // Drop other columns
            $columns = [
                'request_number', 'quantity_requested', 'quantity_approved',
                'unit', 'required_date', 'purpose', 'priority'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('project_material_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
