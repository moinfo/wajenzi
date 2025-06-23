<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayrollArrearsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payroll_arrears', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('payroll_id')->unsigned()->index();
            $table->bigInteger('staff_id')->unsigned()->index();
//            $table->bigInteger('arrears_id')->unsigned()->index();
            $table->decimal('amount', 19,4);
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
        Schema::dropIfExists('payroll_arrears');
    }
}
