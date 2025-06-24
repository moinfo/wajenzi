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
        Schema::create('sub_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('activity_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('estimated_duration_hours', 8, 2)->nullable();
            $table->enum('duration_unit', ['hours', 'days', 'weeks'])->default('days');
            $table->integer('labor_requirement')->nullable();
            $table->enum('skill_level', ['unskilled', 'semi_skilled', 'skilled', 'specialist'])->default('semi_skilled');
            $table->boolean('can_run_parallel')->default(false);
            $table->boolean('weather_dependent')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->foreign('activity_id')->references('id')->on('activities')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_activities');
    }
};