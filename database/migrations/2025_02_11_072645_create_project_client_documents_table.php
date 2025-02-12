<?php
// create_project_client_documents_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectClientDocumentsTable extends Migration
{
    public function up()
    {
        Schema::create('project_client_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('project_clients')->onDelete('cascade');
            $table->string('document_type', 50);
            $table->string('file', 255);
            $table->timestamp('uploaded_at')->useCurrent();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('project_client_documents');
    }
}
