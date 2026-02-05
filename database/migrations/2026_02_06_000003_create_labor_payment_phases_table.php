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

        Schema::create('labor_payment_phases', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->unsignedBigInteger('labor_contract_id');

            // Phase details
            $table->integer('phase_number');
            $table->string('phase_name', 100);
            $table->text('description')->nullable();

            // Amount
            $table->decimal('percentage', 5, 2);
            $table->decimal('amount', 15, 2);
            $table->date('due_date')->nullable();
            $table->string('milestone_description')->nullable();

            // Status and payment
            $table->enum('status', ['pending', 'due', 'approved', 'paid', 'held'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['labor_contract_id', 'phase_number']);
            $table->index('status');
        });

        // Add foreign keys separately
        Schema::table('labor_payment_phases', function (Blueprint $table) {
            $table->foreign('labor_contract_id')->references('id')->on('labor_contracts')->cascadeOnDelete();
            $table->foreign('paid_by')->references('id')->on('users')->nullOnDelete();
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        Schema::dropIfExists('labor_payment_phases');
    }
};
