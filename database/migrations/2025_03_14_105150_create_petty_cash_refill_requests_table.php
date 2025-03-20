<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePettyCashRefillRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('petty_cash_refill_requests', function (Blueprint $table) {
            $table->id();
            $table->string('document_number');
            $table->decimal('balance',20,2);
            $table->decimal('refill_amount',20,2);
            $table->enum('status',['CREATED','PENDING','APPROVED','REJECTED','REFILLED'])->default('CREATED');
            $table->integer('create_by_id')->default(1);
            $table->string('file')->nullable();
            $table->date('date');
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
        Schema::dropIfExists('petty_cash_refill_requests');
    }
}
