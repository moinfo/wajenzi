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
        // Drop dependent tables first
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Schema::dropIfExists('project_schedule_activities');
        Schema::dropIfExists('project_assignments');
        Schema::dropIfExists('project_schedules');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        Schema::create('project_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['draft', 'pending_confirmation', 'confirmed', 'in_progress', 'completed', 'cancelled'])->default('draft');
            $table->unsignedBigInteger('assigned_architect_id')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('lead_id');
            $table->index('client_id');
            $table->index('assigned_architect_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_schedules');
    }
};
