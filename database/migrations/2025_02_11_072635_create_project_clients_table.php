<?php
// Migrations
// create_project_clients_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectClientsTable extends Migration
{
    public function up()
    {
        Schema::create('project_clients', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 50);
            $table->string('last_name', 50);
            $table->string('email', 100)->unique();
            $table->string('phone_number', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('identification_number', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('project_clients');
    }
}
