<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('boq_template_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit', 50)->nullable();
            $table->decimal('base_price', 15, 2)->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->timestamps();
            
            $table->foreign('category_id')->references('id')->on('boq_item_categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boq_template_items');
    }
};