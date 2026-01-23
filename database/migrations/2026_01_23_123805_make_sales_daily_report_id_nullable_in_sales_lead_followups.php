<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the foreign key constraint first
        Schema::table('sales_lead_followups', function (Blueprint $table) {
            $table->dropForeign(['sales_daily_report_id']);
        });

        // Make the column nullable
        Schema::table('sales_lead_followups', function (Blueprint $table) {
            $table->unsignedBigInteger('sales_daily_report_id')->nullable()->change();
        });

        // Re-add the foreign key with ON DELETE SET NULL
        Schema::table('sales_lead_followups', function (Blueprint $table) {
            $table->foreign('sales_daily_report_id')
                  ->references('id')
                  ->on('sales_daily_reports')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_lead_followups', function (Blueprint $table) {
            $table->dropForeign(['sales_daily_report_id']);
        });

        // Set any NULL values to a default before making non-nullable
        DB::table('sales_lead_followups')->whereNull('sales_daily_report_id')->delete();

        Schema::table('sales_lead_followups', function (Blueprint $table) {
            $table->unsignedBigInteger('sales_daily_report_id')->nullable(false)->change();
            $table->foreign('sales_daily_report_id')
                  ->references('id')
                  ->on('sales_daily_reports')
                  ->onDelete('cascade');
        });
    }
};
