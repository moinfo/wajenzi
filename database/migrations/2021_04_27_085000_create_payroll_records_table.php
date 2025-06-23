<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayrollRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payroll_records', function (Blueprint $table) {
            $table->id();
            $table->decimal('staff_id',20,2);
            $table->decimal('advanceSalary',20,2);
            $table->decimal('basicSalary',20,2);
            $table->decimal('allowance',20,2);
            $table->decimal('employeeHealth',20,2);
            $table->decimal('employerHealth',20,2);
            $table->decimal('employeePension',20,2);
            $table->decimal('employerPension',20,2);
            $table->decimal('grossPay',20,2);
            $table->decimal('heslb',20,2);
            $table->decimal('loanBalance',20,2);
            $table->decimal('loanDeduction',20,2);
            $table->string('name');
            $table->decimal('net',20,2);
            $table->decimal('paye',20,2);
            $table->decimal('sdl',20,2);
            $table->decimal('taxable',20,2);
            $table->decimal('totalLoan',20,2);
            $table->decimal('wpf',20,2);
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
        Schema::dropIfExists('payroll_records');
    }
}
