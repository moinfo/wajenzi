<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Disable foreign key checks to avoid issues with existing data
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Schema::table('projects', function (Blueprint $table) {
            // Service type relationship
            if (!Schema::hasColumn('projects', 'service_type_id')) {
                $table->unsignedBigInteger('service_type_id')->nullable()->after('project_type_id');
            }

            // Financial details
            if (!Schema::hasColumn('projects', 'contract_value')) {
                $table->decimal('contract_value', 18, 2)->nullable()->after('actual_end_date');
            }

            // Team assignments
            if (!Schema::hasColumn('projects', 'salesperson_id')) {
                $table->unsignedBigInteger('salesperson_id')->nullable()->after('contract_value');
            }

            if (!Schema::hasColumn('projects', 'project_manager_id')) {
                $table->unsignedBigInteger('project_manager_id')->nullable()->after('salesperson_id');
            }

            // Additional tracking fields
            if (!Schema::hasColumn('projects', 'description')) {
                $table->text('description')->nullable()->after('project_name');
            }

            if (!Schema::hasColumn('projects', 'priority')) {
                $table->string('priority', 20)->default('normal')->after('status');
            }
        });

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $columns = [
                'service_type_id',
                'contract_value',
                'salesperson_id',
                'project_manager_id',
                'description',
                'priority',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('projects', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
