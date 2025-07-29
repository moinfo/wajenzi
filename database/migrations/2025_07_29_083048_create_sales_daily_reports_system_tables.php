<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Main sales daily reports table
        Schema::create('sales_daily_reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_date');
            $table->foreignId('prepared_by')->constrained('users');
            $table->string('department')->default('Sales & Business Development');
            $table->text('daily_summary');
            $table->text('notes_recommendations')->nullable();
            $table->enum('status', ['DRAFT', 'PENDING', 'APPROVED', 'REJECTED'])->default('DRAFT');
            $table->timestamps();
            
            $table->index(['report_date', 'prepared_by']);
            $table->index('status');
        });

        // Lead follow-ups and interactions table
        Schema::create('sales_lead_followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_daily_report_id')->constrained('sales_daily_reports')->onDelete('cascade');
            $table->string('lead_name');
            $table->foreignId('client_source_id')->nullable()->constrained('client_sources');
            $table->text('details_discussion');
            $table->text('outcome');
            $table->text('next_step');
            $table->date('followup_date')->nullable();
            $table->timestamps();
            
            $table->index('sales_daily_report_id');
        });

        // Sales activities table
        Schema::create('sales_report_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_daily_report_id')->constrained('sales_daily_reports')->onDelete('cascade');
            $table->string('invoice_no')->nullable();
            $table->decimal('invoice_sum', 10, 2);
            $table->string('activity');
            $table->enum('status', ['paid', 'not_paid', 'partial'])->default('not_paid');
            $table->timestamps();
            
            $table->index('sales_daily_report_id');
        });

        // Customer acquisition cost table
        Schema::create('sales_customer_acquisition_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_daily_report_id')->constrained('sales_daily_reports')->onDelete('cascade');
            $table->decimal('marketing_cost', 10, 2)->default(0);
            $table->decimal('sales_cost', 10, 2)->default(0);
            $table->decimal('other_cost', 10, 2)->default(0);
            $table->decimal('total_cost', 10, 2)->default(0);
            $table->integer('new_customers')->default(0);
            $table->decimal('cac_value', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('sales_daily_report_id');
        });

        // Client concerns table
        Schema::create('sales_client_concerns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_daily_report_id')->constrained('sales_daily_reports')->onDelete('cascade');
            $table->string('client_name');
            $table->text('issue_concern');
            $table->text('action_taken');
            $table->timestamps();
            
            $table->index('sales_daily_report_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sales_client_concerns');
        Schema::dropIfExists('sales_customer_acquisition_costs');
        Schema::dropIfExists('sales_report_activities');
        Schema::dropIfExists('sales_lead_followups');
        Schema::dropIfExists('sales_daily_reports');
    }
};