<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_service_designs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->unsignedBigInteger('triggered_by_structural_design_id')->nullable();
            $table->foreign('triggered_by_structural_design_id', 'svc_triggered_by_structural_fk')
                  ->references('id')->on('project_structural_designs')->nullOnDelete();
            $table->foreignId('assigned_engineer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['pending', 'in_progress', 'submitted', 'approved', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Work schedule gate
            $table->text('schedule_description')->nullable();
            $table->date('schedule_planned_start')->nullable();
            $table->date('schedule_planned_end')->nullable();
            $table->enum('schedule_status', ['not_submitted', 'submitted', 'approved', 'rejected'])->default('not_submitted');
            $table->timestamp('schedule_submitted_at')->nullable();
            $table->timestamp('schedule_approved_at')->nullable();
            $table->foreignId('schedule_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('schedule_rejection_notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_service_designs');
    }
};
