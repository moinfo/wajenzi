<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStatutoryPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('statutory_payments', function (Blueprint $table) {
            $table->id();
            $table->integer('sub_category_id');
            $table->integer('statutory_payment_id');
            $table->text('description')->nullable();
            $table->string('file')->nullable();
            $table->date('issue_date');
            $table->date('due_date');
            $table->unsignedBigInteger('control_number')->nullable();
            $table->integer('amount');
            $table->enum('status', ['CREATED','PENDING','APPROVED','REJECTED','PAID','COMPLETED'])->default('CREATED');
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
        Schema::dropIfExists('statutory_payments');
    }
}
