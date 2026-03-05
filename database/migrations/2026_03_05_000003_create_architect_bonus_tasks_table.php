<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('architect_bonus_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('task_number')->unique();
            $table->string('project_name');
            $table->foreignId('architect_id')->constrained('users')->onDelete('cascade');
            $table->decimal('project_budget', 15, 2);
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('project_schedule_id')->nullable()->constrained('project_schedules')->nullOnDelete();
            $table->date('start_date');
            $table->date('scheduled_completion_date');
            $table->date('actual_completion_date')->nullable();
            $table->integer('max_units');
            $table->decimal('design_quality_score', 3, 2)->nullable(); // 0.40 - 1.00
            $table->integer('client_revisions')->nullable();
            $table->decimal('schedule_performance', 4, 3)->nullable();
            $table->decimal('client_approval_efficiency', 4, 3)->nullable();
            $table->decimal('performance_score', 4, 3)->nullable();
            $table->decimal('final_units', 6, 2)->nullable();
            $table->decimal('bonus_amount', 12, 2)->default(0);
            $table->enum('status', ['pending', 'in_progress', 'completed', 'scored', 'paid', 'no_bonus'])->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('scored_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('scored_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('architect_bonus_tasks');
    }
};
