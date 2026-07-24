<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Link each payroll advance-salary deduction back to the originating advance,
     * so the recovered amount (and remaining balance) of a payment plan can be
     * tracked across payrolls. Nullable: legacy lump-sum rows stay NULL.
     */
    public function up(): void
    {
        Schema::table('payroll_advance_salaries', function (Blueprint $table) {
            $table->integer('advance_salary_id')->nullable()->after('payroll_id');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_advance_salaries', function (Blueprint $table) {
            $table->dropColumn('advance_salary_id');
        });
    }
};
