<?php
// create_project_site_visits_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectSiteVisitsTable extends Migration
{
    public function up()
    {
        Schema::create('project_site_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('inspector_id')->constrained('users');
            $table->date('visit_date');
            $table->string('status', 20)->default('scheduled');
            $table->text('findings')->nullable();
            $table->text('recommendations')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('project_site_visits');
    }
}
