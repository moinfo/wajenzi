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
        Schema::create('boq_template_sub_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('boq_template_activity_id');
            $table->unsignedBigInteger('sub_activity_id');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->foreign('boq_template_activity_id')->references('id')->on('boq_template_activities')->onDelete('cascade');
            $table->foreign('sub_activity_id')->references('id')->on('sub_activities')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boq_template_sub_activities');
    }
};