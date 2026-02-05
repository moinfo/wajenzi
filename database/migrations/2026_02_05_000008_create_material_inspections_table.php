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

        Schema::create('material_inspections', function (Blueprint $table) {
            $table->id();
            $table->string('inspection_number', 50);
            $table->unsignedBigInteger('supplier_receiving_id');
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('boq_item_id')->nullable();

            // Inspection details
            $table->date('inspection_date');
            $table->decimal('quantity_delivered', 10, 2);
            $table->decimal('quantity_accepted', 10, 2)->default(0);
            $table->decimal('quantity_rejected', 10, 2)->default(0);

            // Assessment
            $table->enum('overall_condition', ['excellent', 'good', 'acceptable', 'poor', 'rejected'])->default('good');
            $table->enum('overall_result', ['pass', 'fail', 'conditional'])->default('pass');
            $table->text('rejection_reason')->nullable();
            $table->text('inspection_notes')->nullable();

            // Inspection criteria checklist (JSON for flexibility)
            $table->json('criteria_checklist')->nullable();

            // Personnel
            $table->unsignedBigInteger('inspector_id');
            $table->unsignedBigInteger('verifier_id')->nullable();

            // Signatures
            $table->string('inspector_signature')->nullable();
            $table->string('verifier_signature')->nullable();

            // Stock integration
            $table->boolean('stock_updated')->default(false);
            $table->timestamp('stock_updated_at')->nullable();

            // Approval workflow status
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected'])->default('draft');

            // Document tracking
            $table->string('document_number', 50)->nullable();

            $table->timestamps();

            // Indexes
            $table->index('inspection_number');
            $table->index('status');
            $table->index(['project_id', 'boq_item_id']);
        });

        Schema::table('material_inspections', function (Blueprint $table) {
            $table->foreign('supplier_receiving_id')->references('id')->on('supplier_receivings')->onDelete('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('boq_item_id')->references('id')->on('project_boq_items')->nullOnDelete();
            $table->foreign('inspector_id')->references('id')->on('users');
            $table->foreign('verifier_id')->references('id')->on('users')->nullOnDelete();
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_inspections');
    }
};
