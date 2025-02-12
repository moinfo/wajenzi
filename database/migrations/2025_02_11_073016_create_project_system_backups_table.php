<?php
// create_project_system_backups_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectSystemBackupsTable extends Migration
{
    public function up()
    {
        Schema::create('project_system_backups', function (Blueprint $table) {
            $table->id();
            $table->timestamp('backup_date')->useCurrent();
            $table->string('backup_path', 255);
            $table->string('status', 20)->default('pending');
            $table->decimal('size_in_mb', 10, 2);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('project_system_backups');
    }
}
