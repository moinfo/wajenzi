<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_boq_items', function (Blueprint $table) {
            // Item identification
            if (!Schema::hasColumn('project_boq_items', 'item_code')) {
                $table->string('item_code', 50)->nullable()->after('id');
            }

            if (!Schema::hasColumn('project_boq_items', 'category_id')) {
                $table->foreignId('category_id')->nullable()->after('item_code')
                    ->constrained('item_categories')->nullOnDelete();
            }

            if (!Schema::hasColumn('project_boq_items', 'specification')) {
                $table->text('specification')->nullable()->after('description');
            }

            // Quantity tracking for procurement workflow
            if (!Schema::hasColumn('project_boq_items', 'quantity_requested')) {
                $table->decimal('quantity_requested', 10, 2)->default(0)->after('total_price');
            }

            if (!Schema::hasColumn('project_boq_items', 'quantity_ordered')) {
                $table->decimal('quantity_ordered', 10, 2)->default(0)->after('quantity_requested');
            }

            if (!Schema::hasColumn('project_boq_items', 'quantity_received')) {
                $table->decimal('quantity_received', 10, 2)->default(0)->after('quantity_ordered');
            }

            if (!Schema::hasColumn('project_boq_items', 'quantity_used')) {
                $table->decimal('quantity_used', 10, 2)->default(0)->after('quantity_received');
            }

            // Procurement status tracking
            if (!Schema::hasColumn('project_boq_items', 'procurement_status')) {
                $table->enum('procurement_status', ['not_started', 'in_progress', 'complete'])
                    ->default('not_started')->after('quantity_used');
            }

            // Add index for item_code lookups
            $table->index('item_code');
            $table->index('procurement_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_boq_items', function (Blueprint $table) {
            // Drop foreign keys first
            if (Schema::hasColumn('project_boq_items', 'category_id')) {
                $table->dropForeign(['category_id']);
                $table->dropColumn('category_id');
            }

            // Drop other columns
            $columns = [
                'item_code', 'specification', 'quantity_requested',
                'quantity_ordered', 'quantity_received', 'quantity_used',
                'procurement_status'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('project_boq_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
