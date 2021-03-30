<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeaveRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('staff_id')->unsigned()->index();
            $table->string('document_number');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['CREATED', 'SUBMITTED', 'APPROVED', 'REJECTED', 'CLOSED', 'PAID'])->default('CREATED');
            $table->dateTime('submitted_date');
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
        Schema::dropIfExists('leave_requests');
    }
}
