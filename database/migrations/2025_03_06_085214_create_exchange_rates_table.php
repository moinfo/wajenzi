<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExchangeRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->integer('foreign_currency_id');
            $table->integer('base_currency_id');
            $table->double('rate')->nullable();
            $table->integer('month');
            $table->integer('year');
            $table->dateTime('entry_timestamp')->useCurrent();
            $table->timestamps();


            // Foreign keys if needed
            // $table->foreign('foreign_currency_id')->references('id')->on('currencies');
            // $table->foreign('base_currency_id')->references('id')->on('currencies');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('exchange_rates');
    }
}
