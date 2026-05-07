<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_structural_design_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('structural_design_id')
                ->constrained('project_structural_designs')
                ->cascadeOnDelete();
            $table->string('name', 100);
            $table->unsignedTinyInteger('stage_order');
            $table->string('status', 20)->default('pending'); // pending/in_progress/completed
            $table->string('file_path', 500)->nullable();
            $table->string('file_name', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_structural_design_stages');
    }
};
