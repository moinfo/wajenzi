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

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id');
            $table->unsignedBigInteger('boq_item_id')->nullable();
            $table->unsignedBigInteger('item_id')->nullable();

            // Item details
            $table->string('description');
            $table->string('unit', 20);
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_price', 15, 2);

            // Receiving tracking
            $table->decimal('quantity_received', 10, 2)->default(0);
            $table->decimal('quantity_pending', 10, 2)->virtualAs('quantity - quantity_received');

            // Status
            $table->enum('status', ['pending', 'partial', 'complete'])->default('pending');

            $table->timestamps();

            // Indexes
            $table->index(['purchase_id', 'boq_item_id']);
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->foreign('purchase_id')->references('id')->on('purchases')->onDelete('cascade');
            $table->foreign('boq_item_id')->references('id')->on('project_boq_items')->nullOnDelete();
            $table->foreign('item_id')->references('id')->on('items')->nullOnDelete();
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
