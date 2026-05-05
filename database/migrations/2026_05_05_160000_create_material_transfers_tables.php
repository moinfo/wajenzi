<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('material_transfers')) {
            Schema::create('material_transfers', function (Blueprint $table) {
                $table->id();
                $table->string('transfer_number')->unique();
                $table->unsignedBigInteger('from_project_id');
                $table->unsignedBigInteger('to_project_id');
                $table->unsignedBigInteger('material_request_id')->nullable();
                $table->unsignedBigInteger('requester_id');
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->string('status', 20)->default('pending');
                $table->date('transfer_date');
                $table->date('expected_arrival_date')->nullable();
                $table->decimal('loading_cost', 15, 2)->default(0);
                $table->decimal('offloading_cost', 15, 2)->default(0);
                $table->decimal('transportation_cost', 15, 2)->default(0);
                $table->decimal('total_cost', 15, 2)->default(0);
                $table->unsignedBigInteger('expenses_sub_category_id')->nullable();
                $table->string('vehicle_info')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('approved_date')->nullable();
                $table->unsignedBigInteger('expense_id')->nullable();
                $table->timestamps();

                $table->index('from_project_id');
                $table->index('to_project_id');
                $table->index('status');
            });
        }

        if (!Schema::hasTable('material_transfer_items')) {
            Schema::create('material_transfer_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('material_transfer_id');
                $table->unsignedBigInteger('source_boq_item_id')->nullable();
                $table->unsignedBigInteger('destination_boq_item_id')->nullable();
                $table->string('description');
                $table->decimal('quantity', 15, 2);
                $table->string('unit', 50);
                $table->string('specification')->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->index('material_transfer_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('material_transfer_items');
        Schema::dropIfExists('material_transfers');
    }
};
