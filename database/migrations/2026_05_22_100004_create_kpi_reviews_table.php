<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One performance review per (employee, period). This is the Approvable model:
 * the RingleSoft approval workflow Supervisor → MD → CEO is tracked separately
 * in approvals/approval_flow_steps. `status` mirrors the approval lifecycle for
 * convenient UI filtering.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('kpi_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('review_number', 30)->unique();          // e.g. KPI-2026-05-0001
            $table->foreignId('kpi_template_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete(); // snapshot of supervisor at submission

            $table->string('period_label', 60);                     // 'AUGUST 2025', 'JUNE 2025'
            $table->date('period_start');
            $table->date('period_end');

            $table->enum('status', [
                'draft',                // employee still editing self-assessment
                'self_submitted',       // employee submitted; awaiting supervisor
                'supervisor_reviewed',  // supervisor scored; awaiting MD
                'md_reviewed',          // MD reviewed; awaiting CEO
                'completed',            // CEO finalised
                'rejected',
                'returned',
            ])->default('draft');

            // Footer fields (free-form sections at end of the form)
            $table->text('achievements')->nullable();
            $table->text('areas_of_improvement')->nullable();
            $table->text('training_needs')->nullable();
            $table->text('employee_comments')->nullable();
            $table->text('supervisor_comments')->nullable();
            $table->text('md_comments')->nullable();
            $table->text('ceo_comments')->nullable();

            // Computed score snapshots (auto-recalculated on rating saves)
            $table->decimal('total_self_score', 6, 2)->nullable();
            $table->decimal('total_supervisor_score', 6, 2)->nullable();
            $table->decimal('total_overall_score', 6, 2)->nullable();
            $table->string('grade_label', 60)->nullable();          // 'Excellent','Very Good',...

            // Lifecycle timestamps for audit
            $table->timestamp('self_submitted_at')->nullable();
            $table->timestamp('supervisor_reviewed_at')->nullable();
            $table->timestamp('md_reviewed_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['employee_id', 'period_start']);
            $table->index('status');
            $table->unique(['employee_id', 'kpi_template_id', 'period_start'], 'kpi_reviews_emp_tpl_period_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_reviews');
    }
};
