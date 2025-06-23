<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeductionSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deduction_settings', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('deduction_id')->unsigned()->index();
            $table->decimal('minimum_amount', 19, 4);
            $table->decimal('maximum_amount', 19, 4);
            $table->decimal('employee_percentage', 8, 4);
            $table->decimal('employer_percentage', 8, 4);
            $table->decimal('additional_amount', 19, 4);
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
        Schema::dropIfExists('deduction_settings');
    }
}
