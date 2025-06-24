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
        Schema::create('sub_activity_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sub_activity_id');
            $table->unsignedBigInteger('boq_item_id');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->timestamps();
            
            $table->foreign('sub_activity_id')->references('id')->on('sub_activities')->onDelete('cascade');
            $table->foreign('boq_item_id')->references('id')->on('boq_template_items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_activity_materials');
    }
};