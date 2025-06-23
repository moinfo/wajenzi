<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDetailsToAllowanceSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('allowance_subscriptions', function (Blueprint $table) {
            $table->integer('staff_id');
            $table->integer('allowance_id');
            $table->integer('amount');
            $table->date('date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('allowance_subscriptions', function (Blueprint $table) {
            //
        });
    }
}
