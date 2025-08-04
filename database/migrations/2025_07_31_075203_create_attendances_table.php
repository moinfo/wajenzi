<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('device_user_id');
            $table->dateTime('record_time');
            $table->string('ip');
            $table->text('comment')->nullable();
            $table->string('file')->nullable();
            $table->timestamps();
            
            // Add indexes for performance
            $table->index('user_id');
            $table->index('device_user_id');
            $table->index('record_time');
            $table->index(['user_id', 'record_time']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendances');
    }
}