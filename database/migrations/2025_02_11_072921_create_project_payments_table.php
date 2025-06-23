<?php
// create_project_payments_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectPaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('project_payments', function (Blueprint $table) {
            $table->id();
//            $table->foreignId('id')->constrained('project_invoices');
            $table->decimal('amount', 15, 2);
            $table->string('payment_method', 50);
            $table->string('reference_number', 100)->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('project_payments');
    }
}
