<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('project_stock_items')) {
            Schema::create('project_stock_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('project_id');
                $table->string('item_code', 30)->unique();
                $table->string('description');
                $table->string('unit', 50);
                $table->decimal('quantity_on_hand', 15, 2)->default(0);
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by_id')->nullable();
                $table->timestamps();

                $table->index('project_id');
            });
        }

        // Add free-stock FK columns to material_transfer_items
        if (Schema::hasTable('material_transfer_items')) {
            Schema::table('material_transfer_items', function (Blueprint $table) {
                if (!Schema::hasColumn('material_transfer_items', 'source_stock_item_id')) {
                    $table->unsignedBigInteger('source_stock_item_id')->nullable()->after('source_boq_item_id');
                }
                if (!Schema::hasColumn('material_transfer_items', 'destination_stock_item_id')) {
                    $table->unsignedBigInteger('destination_stock_item_id')->nullable()->after('destination_boq_item_id');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('material_transfer_items', function (Blueprint $table) {
            $table->dropColumn(['source_stock_item_id', 'destination_stock_item_id']);
        });
        Schema::dropIfExists('project_stock_items');
    }
};
