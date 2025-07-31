<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAttendanceFieldsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_device_id')->nullable()->after('email');
            $table->unsignedBigInteger('attendance_type_id')->nullable()->after('user_device_id');
            $table->enum('attendance_status', ['ENABLED', 'DISABLED'])->default('ENABLED')->after('attendance_type_id');
            
            $table->foreign('attendance_type_id')->references('id')->on('attendance_types')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['attendance_type_id']);
            $table->dropColumn(['user_device_id', 'attendance_type_id', 'attendance_status']);
        });
    }
}