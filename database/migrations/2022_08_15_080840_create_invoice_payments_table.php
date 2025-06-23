<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('statutory_invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->enum('payment_mode',['CASH','BANK'])->default('CASH');
            $table->integer('invoice_id');
            $table->decimal('amount',20,2);
            $table->text('description')->nullable();
            $table->enum('status', ['CREATED','PENDING','APPROVED','REJECTED','PAID','COMPLETED'])->default('CREATED');
            $table->string('file')->nullable();
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
        Schema::dropIfExists('statutory_invoice_payments');
    }
}
