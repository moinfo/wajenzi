<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_quotation_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_quotation_id');
            $table->unsignedBigInteger('material_request_item_id')->nullable();
            $table->unsignedBigInteger('boq_item_id')->nullable();
            $table->string('description')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->string('unit', 20)->nullable();
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('supplier_quotation_id')->references('id')->on('supplier_quotations')->onDelete('cascade');
            $table->foreign('material_request_item_id')->references('id')->on('project_material_request_items')->nullOnDelete();
            $table->foreign('boq_item_id')->references('id')->on('project_boq_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_quotation_items');
    }
};
