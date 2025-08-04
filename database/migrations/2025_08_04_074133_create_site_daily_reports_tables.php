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
        // Main site daily reports table
        Schema::create('site_daily_reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_date');
            $table->foreignId('site_id')->constrained('sites');
            $table->foreignId('supervisor_id')->constrained('users');
            $table->foreignId('prepared_by')->constrained('users');
            $table->decimal('progress_percentage', 5, 2)->default(0);
            $table->text('next_steps')->nullable();
            $table->text('challenges')->nullable();
            $table->enum('status', ['DRAFT', 'PENDING', 'APPROVED', 'REJECTED'])->default('DRAFT');
            $table->timestamps();
            
            $table->index(['report_date', 'site_id']);
            $table->index('status');
            // Removed unique constraint to allow multiple reports per site per day
            // $table->unique(['site_id', 'report_date']);
        });

        // Site work activities table
        Schema::create('site_work_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_daily_report_id')->constrained('site_daily_reports')->onDelete('cascade');
            $table->text('work_description');
            $table->integer('order_number')->default(1);
            $table->timestamps();
            
            $table->index('site_daily_report_id');
        });

        // Site materials used table
        Schema::create('site_materials_used', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_daily_report_id')->constrained('site_daily_reports')->onDelete('cascade');
            $table->string('material_name');
            $table->string('quantity')->nullable();
            $table->string('unit')->nullable();
            $table->timestamps();
            
            $table->index('site_daily_report_id');
        });

        // Site payments table
        Schema::create('site_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_daily_report_id')->constrained('site_daily_reports')->onDelete('cascade');
            $table->string('payment_description');
            $table->decimal('amount', 12, 2);
            $table->string('payment_to')->nullable();
            $table->timestamps();
            
            $table->index('site_daily_report_id');
        });

        // Site labor needed table
        Schema::create('site_labor_needed', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_daily_report_id')->constrained('site_daily_reports')->onDelete('cascade');
            $table->string('labor_type');
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index('site_daily_report_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_labor_needed');
        Schema::dropIfExists('site_payments');
        Schema::dropIfExists('site_materials_used');
        Schema::dropIfExists('site_work_activities');
        Schema::dropIfExists('site_daily_reports');
    }
};