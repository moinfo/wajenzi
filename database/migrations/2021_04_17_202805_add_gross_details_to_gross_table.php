<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGrossDetailsToGrossTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('grosses', function (Blueprint $table) {
            $table->integer('supervisor_id');
            $table->integer('amount');
            $table->date('date');
            $table->text('description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('grosses', function (Blueprint $table) {
            //
        });
    }
}
