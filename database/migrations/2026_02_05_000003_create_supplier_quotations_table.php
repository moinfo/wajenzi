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

        Schema::create('supplier_quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number', 50);
            $table->unsignedBigInteger('material_request_id');
            $table->unsignedBigInteger('supplier_id');

            // Quotation details
            $table->date('quotation_date');
            $table->date('valid_until')->nullable();
            $table->integer('delivery_time_days')->nullable();
            $table->string('payment_terms', 100)->nullable();

            // Pricing
            $table->decimal('unit_price', 15, 2);
            $table->decimal('quantity', 10, 2);
            $table->decimal('total_amount', 15, 2);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->virtualAs('total_amount + vat_amount');

            // File attachment
            $table->string('file')->nullable();

            // Status tracking
            $table->enum('status', ['received', 'selected', 'rejected'])->default('received');

            // Metadata
            $table->unsignedBigInteger('created_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('quotation_number');
            $table->index('status');
            $table->index(['material_request_id', 'supplier_id']);
        });

        Schema::table('supplier_quotations', function (Blueprint $table) {
            $table->foreign('material_request_id')->references('id')->on('project_material_requests')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_quotations');
    }
};
