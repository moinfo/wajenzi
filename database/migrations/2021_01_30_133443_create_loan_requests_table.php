<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoanRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loan_requests', function (Blueprint $table) {
            $table->id();
            $table->string('document_number');
            $table->bigInteger('staff_id')->unsigned()->index();
            $table->text('description');
            $table->decimal('amount', 19,4);
            $table->decimal('instalment_amount', 19,4);
            $table->dateTime('start_date');
            $table->enum('status', ['CREATED', 'SUBMITTED', 'APPROVED', 'REJECTED', 'ACTIVE', 'SUSPENDED', 'PAID'])->default('CREATED');
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
        Schema::dropIfExists('loan_requests');
    }
}
