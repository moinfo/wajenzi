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
        Schema::dropIfExists('project_activity_templates');
        Schema::create('project_activity_templates', function (Blueprint $table) {
            $table->id();
            $table->string('activity_code', 10)->unique(); // A0, A1, B1, etc.
            $table->string('name'); // Activity name
            $table->string('phase'); // Survey stage, 2D Design Stage, etc.
            $table->string('discipline'); // Architectural Drawing 1st Draft, Client, etc.
            $table->integer('duration_days'); // Duration in working days
            $table->string('predecessor_code', 10)->nullable(); // A0, A1, etc. or null for Start
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_activity_templates');
    }
};
