<?php
// create_project_designs_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectDesignsTable extends Migration
{
    public function up()
    {
        Schema::create('project_designs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('designer_id')->constrained('users');
            $table->integer('version');
            $table->string('design_type', 50);
            $table->string('file', 255);
            $table->string('status', 20)->default('draft');
            $table->text('client_feedback')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('project_designs');
    }
}
