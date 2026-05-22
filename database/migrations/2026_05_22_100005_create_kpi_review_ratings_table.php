<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per KPI item per review, holding the self / supervisor / overall scores.
 * Snapshot columns freeze kpa/measure/target/weight at review-creation time so
 * later edits to kpi_items don't rewrite history. Scores are 0..100 (percentage).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('kpi_review_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_review_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kpi_item_id')->nullable()->constrained('kpi_items')->nullOnDelete();

            // Snapshots (preserved even if the underlying kpi_item is later edited or deleted)
            $table->string('kpa_snapshot', 255);
            $table->text('measure_snapshot');
            $table->text('target_snapshot')->nullable();
            $table->decimal('weight_snapshot', 5, 2);
            $table->string('section_code_snapshot', 40);           // 'A','B','financial',...

            $table->text('actual_achieved')->nullable();           // 'Actual Achieved' column (Sales)
            $table->decimal('self_rate', 5, 2)->nullable();        // 0..100 percentage
            $table->decimal('supervisor_rate', 5, 2)->nullable();
            $table->decimal('overall_rate', 5, 2)->nullable();
            $table->text('comment')->nullable();

            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['kpi_review_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_review_ratings');
    }
};
