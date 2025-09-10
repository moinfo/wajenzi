<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBillingDocumentItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('billing_document_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->enum('item_type', ['product', 'service', 'custom'])->default('custom');
            $table->unsignedBigInteger('product_service_id')->nullable();
            $table->string('item_code')->nullable();
            $table->string('item_name');
            $table->text('description')->nullable();
            $table->decimal('quantity', 15, 4)->default(1);
            $table->string('unit_of_measure')->nullable();
            $table->decimal('unit_price', 20, 2);
            $table->enum('discount_type', ['percentage', 'fixed'])->nullable();
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->decimal('discount_amount', 20, 2)->default(0);
            $table->unsignedBigInteger('tax_rate_id')->nullable();
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('tax_amount', 20, 2)->default(0);
            $table->decimal('line_total', 20, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index('document_id');
            $table->index('product_service_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('billing_document_items');
    }
}