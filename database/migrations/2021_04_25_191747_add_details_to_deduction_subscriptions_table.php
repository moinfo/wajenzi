<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDetailsToDeductionSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deduction_subscriptions', function (Blueprint $table) {
            $table->integer('staff_id');
            $table->integer('deduction_id');
            $table->string('membership_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deduction_subscriptions', function (Blueprint $table) {
            //
        });
    }
}
