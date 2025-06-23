<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeductionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deductions', function (Blueprint $table) {
            $table->id();
            $table->enum('nature', ['GROSS', 'NET', 'TAXABLE'])->nullable();
            $table->string('name');
            $table->string('abbreviation');
            $table->text('description')->nullable();
            $table->string('registration_number')->nullable();
            $table->text('postal_address')->nullable();
            $table->bigInteger('dr_account_id')->unsigned()->index()->nullable();
            $table->bigInteger('cr_account_id')->unsigned()->index()->nullable();
            $table->bigInteger('logo_file_id')->unsigned()->index()->nullable();
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
        Schema::dropIfExists('deductions');
    }
}
