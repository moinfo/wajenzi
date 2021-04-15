<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeavePlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leave_plans', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('staff_id')->unsigned()->index();
            $table->bigInteger('leave_type_id')->unsigned()->index()->default(1);
            $table->integer('year');
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
        Schema::dropIfExists('leave_plans');
    }
}
