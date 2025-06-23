<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToTransactionMovementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_movements', function (Blueprint $table) {
            $table->enum('status', ['CREATED','PENDING','APPROVED','REJECTED','PAID','COMPLETED'])->default('CREATED');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_movements', function (Blueprint $table) {
            //
        });
    }
}
