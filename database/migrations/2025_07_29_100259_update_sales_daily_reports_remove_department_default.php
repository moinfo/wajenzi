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
        Schema::table('sales_daily_reports', function (Blueprint $table) {
            // Remove default value from department field and set existing records to null
            $table->string('department')->nullable()->default(null)->change();
        });
        
        // Update existing records that have the default department value to null
        DB::table('sales_daily_reports')
            ->where('department', 'Sales & Business Development')
            ->whereNotNull('department_id')
            ->update(['department' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_daily_reports', function (Blueprint $table) {
            // Restore default value
            $table->string('department')->default('Sales & Business Development')->change();
        });
    }
};
