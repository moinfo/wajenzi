<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_stock_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 30)->unique();
            $table->unsignedBigInteger('project_id');
            $table->string('supplier')->nullable();
            $table->date('receipt_date');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();

            $table->index('project_id');
        });

        Schema::create('project_stock_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('receipt_id');
            $table->unsignedBigInteger('stock_item_id')->nullable();
            $table->string('description');
            $table->string('unit', 50);
            $table->decimal('quantity', 15, 2);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('receipt_id');
            $table->index('stock_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_stock_receipt_items');
        Schema::dropIfExists('project_stock_receipts');
    }
};
