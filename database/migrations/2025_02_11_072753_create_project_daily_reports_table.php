<?php

// create_project_daily_reports_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectDailyReportsTable extends Migration
{
    public function up()
    {
        Schema::create('project_daily_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('supervisor_id')->constrained('users');
            $table->date('report_date');
            $table->text('weather_conditions')->nullable();
            $table->text('work_completed');
            $table->text('materials_used');
            $table->integer('labor_hours');
            $table->text('issues_faced')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('project_daily_reports');
    }
}
