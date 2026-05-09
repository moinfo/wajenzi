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
        Schema::create('structural_design_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('structural_design_id')->constrained('project_structural_designs')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('project_clients')->cascadeOnDelete();
            $table->text('comment');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('structural_design_feedbacks');
    }
};
