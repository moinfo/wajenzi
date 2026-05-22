<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Individual KPI rows shown to the employee/supervisor on the review form.
 * Snapshot of kpa/measure/target/weight is also stored on kpi_review_ratings so
 * historical reviews aren't affected by later template edits.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('kpi_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kpi_template_section_id')->constrained('kpi_template_sections')->cascadeOnDelete();
            $table->string('kpa', 255);                       // 'Administration', 'Cost Estimation & BOQs'
            $table->text('responsibility')->nullable();       // mid-column "Responsibility" text
            $table->text('measure');                          // KPI / Measure text
            $table->text('target')->nullable();               // '95% on-time reporting'
            $table->decimal('weight', 5, 2);                  // 2.00, 5.00, 10.00
            $table->string('measurement_method', 255)->nullable(); // present in Content Creator template
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['kpi_template_section_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_items');
    }
};
