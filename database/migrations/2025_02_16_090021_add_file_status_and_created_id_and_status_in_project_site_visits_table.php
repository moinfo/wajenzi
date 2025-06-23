<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFileStatusAndCreatedIdAndStatusInProjectSiteVisitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('project_site_visits', function (Blueprint $table) {
            $table->enum('status', ['CREATED','PENDING','APPROVED','REJECTED','PAID','COMPLETED'])->default('CREATED');
            $table->integer('create_by_id')->default(1);
            $table->string('file')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('project_site_visits', function (Blueprint $table) {
            //
        });
    }
}
