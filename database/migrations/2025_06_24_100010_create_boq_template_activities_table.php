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
        Schema::create('boq_template_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('boq_template_stage_id');
            $table->unsignedBigInteger('activity_id');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->foreign('boq_template_stage_id')->references('id')->on('boq_template_stages')->onDelete('cascade');
            $table->foreign('activity_id')->references('id')->on('activities')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boq_template_activities');
    }
};