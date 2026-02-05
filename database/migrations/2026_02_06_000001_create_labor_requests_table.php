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

        Schema::create('labor_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 50)->unique();

            // Relationships
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('construction_phase_id')->nullable();
            $table->unsignedBigInteger('artisan_id')->nullable();
            $table->unsignedBigInteger('requested_by');

            // Work details
            $table->text('work_description');
            $table->string('work_location')->nullable();
            $table->integer('estimated_duration_days')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // Assessment and materials
            $table->text('artisan_assessment')->nullable();
            $table->json('materials_list')->nullable();
            $table->boolean('materials_included')->default(false);

            // Amounts
            $table->decimal('proposed_amount', 15, 2)->default(0);
            $table->decimal('negotiated_amount', 15, 2)->nullable();
            $table->decimal('approved_amount', 15, 2)->nullable();
            $table->string('currency', 10)->default('TZS');
            $table->text('payment_terms')->nullable();

            // Status and approval
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'contracted'])->default('draft');
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('request_number');
            $table->index('status');
            $table->index(['project_id', 'status']);
        });

        // Add foreign keys separately
        Schema::table('labor_requests', function (Blueprint $table) {
            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreign('construction_phase_id')->references('id')->on('project_construction_phases')->nullOnDelete();
            $table->foreign('artisan_id')->references('id')->on('suppliers')->nullOnDelete();
            $table->foreign('requested_by')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        Schema::dropIfExists('labor_requests');
    }
};
