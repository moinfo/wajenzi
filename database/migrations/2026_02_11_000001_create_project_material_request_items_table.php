<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_material_request_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('material_request_id');
            $table->unsignedBigInteger('boq_item_id')->nullable();
            $table->decimal('quantity_requested', 10, 2)->default(0);
            $table->decimal('quantity_approved', 10, 2)->nullable();
            $table->string('unit', 20)->nullable();
            $table->string('description')->nullable();
            $table->string('specification')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('material_request_id')
                ->references('id')->on('project_material_requests')
                ->onDelete('cascade');

            $table->foreign('boq_item_id')
                ->references('id')->on('project_boq_items')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_material_request_items');
    }
};
