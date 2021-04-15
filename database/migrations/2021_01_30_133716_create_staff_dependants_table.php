<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStaffDependantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('staff_dependants', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('staff_id')->unsigned()->index();
            $table->enum('relationship', ['WIFE', 'HUSBAND', 'SON', 'DAUGHTER', 'MOTHER', 'FATHER', 'OTHER'])->default('OTHER');
            $table->string('other')->nullable();
            $table->enum('gender', ['MALE', 'FEMALE', 'OTHER'])->nullable();
            $table->string('name');
            $table->date('date_of_birth');
            $table->integer('priority')->nullable();
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
        Schema::dropIfExists('staff_dependants');
    }
}
