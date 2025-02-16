<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropStatusFromSiteVisits extends Migration
{
    public function up()
    {
        Schema::table('project_site_visits', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    public function down()
    {
        Schema::table('project_site_visits', function (Blueprint $table) {
            // Recreate the column in case of rollback
            $table->enum('status', ['CREATED','PENDING','APPROVED','REJECTED','PAID','COMPLETED'])->default('CREATED');
        });
    }
}
