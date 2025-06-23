<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayrollDeductionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payroll_deductions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('payroll_id')->unsigned()->index();
            $table->bigInteger('staff_id')->unsigned()->index();
            $table->bigInteger('deduction_id')->unsigned()->index();
            $table->decimal('deduction_source', 19,4);
            $table->decimal('employee_deduction_amount', 19,4);
            $table->decimal('employer_deduction_amount', 19,4);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payroll_deductions');
    }
}
