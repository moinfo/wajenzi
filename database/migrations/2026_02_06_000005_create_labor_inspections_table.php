<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::create('labor_inspections', function (Blueprint $table) {
            $table->id();
            $table->string('inspection_number', 50)->unique();

            // Relationships
            $table->unsignedBigInteger('labor_contract_id');
            $table->unsignedBigInteger('payment_phase_id')->nullable();
            $table->unsignedBigInteger('inspector_id');

            // Inspection details
            $table->date('inspection_date');
            $table->enum('inspection_type', ['progress', 'milestone', 'final'])->default('progress');

            // Assessment
            $table->enum('work_quality', ['excellent', 'good', 'acceptable', 'poor', 'unacceptable'])->default('good');
            $table->decimal('completion_percentage', 5, 2)->default(0);
            $table->boolean('scope_compliance')->default(true);

            // Defects
            $table->text('defects_found')->nullable();
            $table->boolean('rectification_required')->default(false);
            $table->text('rectification_notes')->nullable();

            // Evidence
            $table->json('photos')->nullable();
            $table->string('inspector_signature')->nullable();

            // Result
            $table->enum('result', ['pass', 'conditional', 'fail'])->default('pass');
            $table->text('notes')->nullable();

            // Approval workflow
            $table->enum('status', ['draft', 'pending', 'verified', 'approved', 'rejected'])->default('draft');
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('inspection_number');
            $table->index('status');
            $table->index(['labor_contract_id', 'inspection_type']);
        });

        // Add foreign keys separately
        Schema::table('labor_inspections', function (Blueprint $table) {
            $table->foreign('labor_contract_id')->references('id')->on('labor_contracts')->cascadeOnDelete();
            $table->foreign('payment_phase_id')->references('id')->on('labor_payment_phases')->nullOnDelete();
            $table->foreign('inspector_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        Schema::dropIfExists('labor_inspections');
    }
};
