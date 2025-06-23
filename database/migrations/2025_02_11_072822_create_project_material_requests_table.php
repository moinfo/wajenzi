<?php
// create_project_material_requests_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectMaterialRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('project_material_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('requester_id')->constrained('users');
            $table->string('status', 20)->default('pending');
            $table->timestamp('requested_date')->useCurrent();
            $table->timestamp('approved_date')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('project_material_requests');
    }
}
