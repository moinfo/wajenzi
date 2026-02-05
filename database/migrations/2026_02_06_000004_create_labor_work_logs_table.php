<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::create('labor_work_logs', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->unsignedBigInteger('labor_contract_id');
            $table->unsignedBigInteger('logged_by');

            // Log details
            $table->date('log_date');
            $table->text('work_done');
            $table->integer('workers_present')->default(1);
            $table->decimal('hours_worked', 5, 2)->nullable();
            $table->decimal('progress_percentage', 5, 2)->nullable();

            // Additional info
            $table->text('challenges')->nullable();
            $table->json('materials_used')->nullable();
            $table->json('photos')->nullable();
            $table->string('weather_conditions', 50)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['labor_contract_id', 'log_date']);
            $table->index('log_date');
        });

        // Add foreign keys separately
        Schema::table('labor_work_logs', function (Blueprint $table) {
            $table->foreign('labor_contract_id')->references('id')->on('labor_contracts')->cascadeOnDelete();
            $table->foreign('logged_by')->references('id')->on('users')->cascadeOnDelete();
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        Schema::dropIfExists('labor_work_logs');
    }
};
