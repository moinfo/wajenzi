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

        Schema::create('labor_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number', 50)->unique();

            // Relationships
            $table->unsignedBigInteger('labor_request_id');
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('artisan_id');
            $table->unsignedBigInteger('supervisor_id')->nullable();

            // Dates
            $table->date('contract_date');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('actual_end_date')->nullable();

            // Work details
            $table->text('scope_of_work');
            $table->text('terms_conditions')->nullable();

            // Financial
            $table->decimal('total_amount', 15, 2);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('balance_amount', 15, 2)->default(0);
            $table->string('currency', 10)->default('TZS');

            // Signatures
            $table->string('artisan_signature')->nullable();
            $table->string('supervisor_signature')->nullable();
            $table->string('contract_file')->nullable();

            // Status
            $table->enum('status', ['draft', 'active', 'on_hold', 'completed', 'terminated'])->default('draft');
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('contract_number');
            $table->index('status');
            $table->index(['project_id', 'status']);
        });

        // Add foreign keys separately
        Schema::table('labor_contracts', function (Blueprint $table) {
            $table->foreign('labor_request_id')->references('id')->on('labor_requests')->cascadeOnDelete();
            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreign('artisan_id')->references('id')->on('suppliers')->cascadeOnDelete();
            $table->foreign('supervisor_id')->references('id')->on('users')->nullOnDelete();
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        Schema::dropIfExists('labor_contracts');
    }
};
