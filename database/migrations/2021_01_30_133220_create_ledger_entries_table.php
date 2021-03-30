<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLedgerEntriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('journal_id')->unsigned()->index();
            $table->bigInteger('account_id')->unsigned()->index();
            $table->bigInteger('currency_id')->unsigned()->index();
            $table->decimal('dr', 19,4)->nullable();
            $table->decimal('cr', 19,4)->nullable();
            $table->decimal('exchange_rate', 19,4)->default(1);
            $table->json('foreign_exchange_rates')->nullable();
            $table->dateTime('transaction_date')->useCurrent();
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
        Schema::dropIfExists('ledger_entries');
    }
}
