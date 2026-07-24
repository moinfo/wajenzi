<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add payment-plan fields so an advance can be recovered as a fixed monthly
     * installment starting from a chosen month, instead of one lump-sum deduction.
     * Nullable so existing advances keep their legacy (date-in-month) behaviour.
     */
    public function up(): void
    {
        Schema::table('advance_salaries', function (Blueprint $table) {
            $table->decimal('monthly_deduction', 19, 4)->nullable()->after('amount');
            $table->unsignedTinyInteger('start_month')->nullable()->after('monthly_deduction');
            $table->integer('start_year')->nullable()->after('start_month');
        });
    }

    public function down(): void
    {
        Schema::table('advance_salaries', function (Blueprint $table) {
            $table->dropColumn(['monthly_deduction', 'start_month', 'start_year']);
        });
    }
};
