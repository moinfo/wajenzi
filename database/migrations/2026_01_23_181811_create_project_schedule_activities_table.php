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
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Schema::dropIfExists('project_schedule_activities');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        Schema::create('project_schedule_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_schedule_id');
            $table->string('activity_code', 10);
            $table->string('name');
            $table->string('phase');
            $table->string('discipline');
            $table->date('start_date');
            $table->integer('duration_days');
            $table->date('end_date');
            $table->string('predecessor_code', 10)->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'skipped'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['project_schedule_id', 'activity_code'], 'psa_schedule_activity_idx');
            $table->index(['assigned_to', 'status'], 'psa_assigned_status_idx');
            $table->index(['start_date', 'end_date'], 'psa_dates_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_schedule_activities');
    }
};
