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

        Schema::create('quotation_comparisons', function (Blueprint $table) {
            $table->id();
            $table->string('comparison_number', 50);
            $table->unsignedBigInteger('material_request_id');
            $table->date('comparison_date');

            // Selected supplier and quotation
            $table->unsignedBigInteger('recommended_supplier_id')->nullable();
            $table->unsignedBigInteger('selected_quotation_id')->nullable();

            // Recommendation details
            $table->text('recommendation_reason')->nullable();

            // Workflow tracking
            $table->unsignedBigInteger('prepared_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_date')->nullable();

            // Status for approval workflow
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected'])->default('draft');

            // Document tracking
            $table->string('document_number', 50)->nullable();

            $table->timestamps();

            // Indexes
            $table->index('comparison_number');
            $table->index('status');
        });

        Schema::table('quotation_comparisons', function (Blueprint $table) {
            $table->foreign('material_request_id')->references('id')->on('project_material_requests')->onDelete('cascade');
            $table->foreign('recommended_supplier_id')->references('id')->on('suppliers')->nullOnDelete();
            $table->foreign('selected_quotation_id')->references('id')->on('supplier_quotations')->nullOnDelete();
            $table->foreign('prepared_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_comparisons');
    }
};
