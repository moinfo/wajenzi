<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Logical groupings within a template. Section A "General Performance" (30%)
 * is shared across all role templates; Section B "Departmental Objectives" (70%)
 * is role-specific. Sales-style templates further sub-section B into
 * Financial / Customer / Internal / Learning perspectives.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('kpi_template_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_template_id')->constrained()->cascadeOnDelete();
            $table->string('code', 40);                    // 'A','B','financial', etc.
            $table->string('title', 160);                  // 'General Performance', 'Departmental Objectives'
            $table->decimal('weight_total', 5, 2);         // 30.00, 70.00
            $table->integer('sort_order')->default(0);
            $table->boolean('is_common')->default(false);  // TRUE for shared Section A
            $table->timestamps();

            $table->index(['kpi_template_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_template_sections');
    }
};
