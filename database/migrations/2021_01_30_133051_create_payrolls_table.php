<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayrollsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->string('document_number');
            $table->string('payroll_number');
            $table->integer('year');
            $table->integer('month');
            $table->enum('status', ['CREATED', 'SUBMITTED', 'APPROVED', 'REJECTED', 'CLOSED', 'PAID'])->default('CREATED');
            $table->dateTime('submitted_date');
            $table->bigInteger('created_by_id')->unsigned()->index();
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
        Schema::dropIfExists('payrolls');
    }
}
